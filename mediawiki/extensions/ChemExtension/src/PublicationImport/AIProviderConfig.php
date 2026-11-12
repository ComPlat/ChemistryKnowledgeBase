<?php

namespace DIQA\ChemExtension\PublicationImport;

use InvalidArgumentException;

/**
 * Immutable description of the LLM backend used for extraction and every other AI task.
 *
 * Supported providers:
 *  - `openai`            OpenAI platform, Responses API + Files API (native PDF input).
 *  - `kit`               KIT KI-Toolbox (https://ki-toolbox.scc.kit.edu), OpenAI-compatible
 *                        Chat Completions gateway. Serves KIT-hosted open-weight models
 *                        (`kit.*`, `alias.*-local`) as well as Azure/Google models (`azure.*`,
 *                        `google.*`) under one key.
 *  - `openai-compatible` Any other OpenAI-compatible Chat Completions server, e.g. a local
 *                        vLLM, Ollama (`http://localhost:11434/v1`), LM Studio or llama.cpp.
 *
 * Configuration (LocalSettings.php):
 *
 *     $wgCEAIProvider = 'kit';                  // 'openai' (default) | 'kit' | 'openai-compatible'
 *     $wgCEAIModel = 'azure.gpt-5.4';           // optional, provider default otherwise
 *     $wgCEAIReasoningEffort = 'medium';        // optional: none|minimal|low|medium|high
 *     $wgCEAIProviders = [                      // optional per-provider overrides
 *         'kit' => ['apiKey' => '...'],
 *         'openai-compatible' => ['baseUrl' => 'http://localhost:11434/v1', 'model' => 'qwen3:32b'],
 *     ];
 *
 * Per-provider keys: baseUrl, apiKey, model, reasoningEffort, pdfInput (auto|native|text),
 * timeout (seconds), maxRetries.
 *
 * API keys may also come from the environment (OPENAI_API_KEY, KIT_TOOLBOX_API_KEY,
 * CE_AI_API_KEY) so they need not be stored in LocalSettings.php. The legacy globals
 * $wgOpenAIKey, $wgOpenAIModel and $wgOpenAIModelReasoning keep working for `openai`.
 */
final class AIProviderConfig
{
    public const OPENAI = 'openai';
    public const KIT = 'kit';
    public const OPENAI_COMPATIBLE = 'openai-compatible';

    /** PDFs are sent as files and read by the model itself (layout, tables, figures). */
    public const PDF_INPUT_NATIVE = 'native';
    /** PDFs are converted to text locally (pdftotext) and sent as plain text. */
    public const PDF_INPUT_TEXT = 'text';
    /** Pick native or text depending on provider and model. */
    public const PDF_INPUT_AUTO = 'auto';

    private const DEFAULTS = [
        self::OPENAI => [
            'baseUrl' => 'https://api.openai.com/v1',
            'model' => 'o3',
            'reasoningEffort' => null,
            'pdfInput' => self::PDF_INPUT_NATIVE,
            'apiKeyEnv' => 'OPENAI_API_KEY',
        ],
        self::KIT => [
            'baseUrl' => 'https://ki-toolbox.scc.kit.edu/api/v1',
            'model' => 'azure.gpt-5.4',
            'reasoningEffort' => null,
            'pdfInput' => self::PDF_INPUT_AUTO,
            'apiKeyEnv' => 'KIT_TOOLBOX_API_KEY',
        ],
        self::OPENAI_COMPATIBLE => [
            'baseUrl' => 'http://localhost:11434/v1',
            'model' => null,
            'reasoningEffort' => null,
            'pdfInput' => self::PDF_INPUT_TEXT,
            'apiKeyEnv' => 'CE_AI_API_KEY',
        ],
    ];

    /**
     * Model-name prefixes on the KIT gateway that are proxied to hosted frontier APIs which
     * accept PDF file parts. Everything else on KIT is served by vLLM, which rejects them.
     */
    private const KIT_NATIVE_PDF_PREFIXES = ['azure.', 'google.'];

    private const REASONING_EFFORTS = ['none', 'minimal', 'low', 'medium', 'high', 'xhigh'];

    private string $provider;
    private string $baseUrl;
    private ?string $apiKey;
    private string $model;
    private ?string $reasoningEffort;
    private string $pdfInput;
    private int $timeout;
    private int $maxRetries;

    private function __construct(string $provider, string $baseUrl, ?string $apiKey, string $model,
                                 ?string $reasoningEffort, string $pdfInput, int $timeout, int $maxRetries)
    {
        $this->provider = $provider;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->model = $model;
        $this->reasoningEffort = $reasoningEffort;
        $this->pdfInput = $pdfInput;
        $this->timeout = $timeout;
        $this->maxRetries = $maxRetries;
    }

    /**
     * Builds the configuration from MediaWiki globals.
     *
     * @param string|null $provider overrides $wgCEAIProvider (e.g. from a maintenance option)
     * @param string|null $model    overrides the configured model
     */
    public static function fromGlobals(?string $provider = null, ?string $model = null): self
    {
        $configuredProvider = $GLOBALS['wgCEAIProvider'] ?? self::OPENAI;
        $provider = $provider ?? $configuredProvider;
        $overrides = $GLOBALS['wgCEAIProviders'][$provider] ?? [];
        if (!is_array($overrides)) {
            throw new InvalidArgumentException("\$wgCEAIProviders['$provider'] must be an array");
        }

        // Legacy OpenAI globals (pre-provider configuration) keep working unchanged.
        if ($provider === self::OPENAI) {
            $overrides += array_filter([
                'apiKey' => $GLOBALS['wgOpenAIKey'] ?? null,
                'model' => $GLOBALS['wgOpenAIModel'] ?? null,
                'reasoningEffort' => $GLOBALS['wgOpenAIModelReasoning'] ?? null,
            ], static fn($v) => $v !== null && $v !== '');
        }

        // $wgCEAIModel/$wgCEAIReasoningEffort belong to $wgCEAIProvider; when another provider
        // is requested explicitly they must not leak into it. An explicit $model always wins.
        $isConfiguredProvider = $provider === $configuredProvider;
        $topLevel = array_filter([
            'model' => $model ?? ($isConfiguredProvider ? ($GLOBALS['wgCEAIModel'] ?? null) : null),
            'reasoningEffort' => $isConfiguredProvider ? ($GLOBALS['wgCEAIReasoningEffort'] ?? null) : null,
        ], static fn($v) => $v !== null && $v !== '');

        return self::fromArray($provider, $topLevel + $overrides);
    }

    /**
     * @param array{baseUrl?:string, apiKey?:?string, model?:string, reasoningEffort?:?string,
     *              pdfInput?:string, timeout?:int, maxRetries?:int} $settings
     */
    public static function fromArray(string $provider, array $settings = []): self
    {
        if (!isset(self::DEFAULTS[$provider])) {
            throw new InvalidArgumentException(sprintf(
                "Unknown AI provider '%s'. Supported: %s", $provider, implode(', ', array_keys(self::DEFAULTS))
            ));
        }
        $defaults = self::DEFAULTS[$provider];

        $model = $settings['model'] ?? $defaults['model'];
        if ($model === null || $model === '') {
            throw new InvalidArgumentException("No model configured for AI provider '$provider'. Set \$wgCEAIModel.");
        }

        $apiKey = $settings['apiKey'] ?? null;
        if ($apiKey === null || $apiKey === '') {
            $env = getenv($defaults['apiKeyEnv']);
            $apiKey = ($env === false || $env === '') ? null : $env;
        }

        $effort = $settings['reasoningEffort'] ?? $defaults['reasoningEffort'];
        if ($effort !== null && !in_array($effort, self::REASONING_EFFORTS, true)) {
            throw new InvalidArgumentException("Invalid reasoning effort '$effort'. Allowed: "
                . implode(', ', self::REASONING_EFFORTS));
        }

        $pdfInput = $settings['pdfInput'] ?? $defaults['pdfInput'];
        if (!in_array($pdfInput, [self::PDF_INPUT_AUTO, self::PDF_INPUT_NATIVE, self::PDF_INPUT_TEXT], true)) {
            throw new InvalidArgumentException("Invalid pdfInput '$pdfInput'. Allowed: auto, native, text");
        }

        return new self(
            $provider,
            $settings['baseUrl'] ?? $defaults['baseUrl'],
            $apiKey,
            $model,
            $effort,
            $pdfInput,
            (int)($settings['timeout'] ?? 600),
            (int)($settings['maxRetries'] ?? 3)
        );
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getReasoningEffort(): ?string
    {
        return $this->reasoningEffort;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    /**
     * Whether the backend needs an API key. Local OpenAI-compatible servers usually do not.
     */
    public function requiresApiKey(): bool
    {
        return $this->provider !== self::OPENAI_COMPATIBLE;
    }

    /**
     * Resolves `auto` to the concrete PDF input mode for the configured model.
     *
     * @return string self::PDF_INPUT_NATIVE or self::PDF_INPUT_TEXT
     */
    public function getPdfInputMode(): string
    {
        if ($this->pdfInput !== self::PDF_INPUT_AUTO) {
            return $this->pdfInput;
        }
        if ($this->provider === self::OPENAI) {
            return self::PDF_INPUT_NATIVE;
        }
        if ($this->provider === self::KIT) {
            foreach (self::KIT_NATIVE_PDF_PREFIXES as $prefix) {
                if (strpos($this->model, $prefix) === 0) {
                    return self::PDF_INPUT_NATIVE;
                }
            }
        }
        return self::PDF_INPUT_TEXT;
    }

    /** Human-readable identifier for logs and status pages, e.g. "kit / azure.gpt-5.4". */
    public function describe(): string
    {
        return $this->provider . ' / ' . $this->model;
    }
}
