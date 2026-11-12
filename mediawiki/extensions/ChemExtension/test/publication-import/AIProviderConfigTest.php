<?php

namespace DIQA\ChemExtension\PublicationImport;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AIProviderConfigTest extends TestCase
{
    private const GLOBALS = ['wgCEAIProvider', 'wgCEAIModel', 'wgCEAIReasoningEffort', 'wgCEAIProviders',
        'wgOpenAIKey', 'wgOpenAIModel', 'wgOpenAIModelReasoning'];
    private const ENV = ['OPENAI_API_KEY', 'KIT_TOOLBOX_API_KEY', 'CE_AI_API_KEY'];

    private array $savedGlobals = [];
    private array $savedEnv = [];

    protected function setUp(): void
    {
        foreach (self::GLOBALS as $name) {
            $this->savedGlobals[$name] = $GLOBALS[$name] ?? null;
            unset($GLOBALS[$name]);
        }
        foreach (self::ENV as $name) {
            $this->savedEnv[$name] = getenv($name);
            putenv($name);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->savedGlobals as $name => $value) {
            if ($value === null) {
                unset($GLOBALS[$name]);
            } else {
                $GLOBALS[$name] = $value;
            }
        }
        foreach ($this->savedEnv as $name => $value) {
            putenv($value === false ? $name : "$name=$value");
        }
    }

    public function testDefaultsToOpenAIWithLegacyGlobals(): void
    {
        $GLOBALS['wgOpenAIKey'] = 'sk-legacy';
        $GLOBALS['wgOpenAIModel'] = 'gpt-5.1';
        $GLOBALS['wgOpenAIModelReasoning'] = 'low';

        $config = AIProviderConfig::fromGlobals();

        $this->assertSame(AIProviderConfig::OPENAI, $config->getProvider());
        $this->assertSame('https://api.openai.com/v1', $config->getBaseUrl());
        $this->assertSame('sk-legacy', $config->getApiKey());
        $this->assertSame('gpt-5.1', $config->getModel());
        $this->assertSame('low', $config->getReasoningEffort());
        $this->assertSame(AIProviderConfig::PDF_INPUT_NATIVE, $config->getPdfInputMode());
    }

    public function testOpenAIModelDefaultsToO3(): void
    {
        $this->assertSame('o3', AIProviderConfig::fromGlobals()->getModel());
    }

    public function testKitReadsKeyFromEnvironment(): void
    {
        $GLOBALS['wgCEAIProvider'] = 'kit';
        putenv('KIT_TOOLBOX_API_KEY=kit-secret');

        $config = AIProviderConfig::fromGlobals();

        $this->assertSame('https://ki-toolbox.scc.kit.edu/api/v1', $config->getBaseUrl());
        $this->assertSame('kit-secret', $config->getApiKey());
        $this->assertSame('azure.gpt-5.4', $config->getModel());
        $this->assertSame('kit / azure.gpt-5.4', $config->describe());
    }

    public function testConfiguredKeyWinsOverEnvironment(): void
    {
        putenv('KIT_TOOLBOX_API_KEY=from-env');
        $config = AIProviderConfig::fromArray('kit', ['apiKey' => 'from-settings']);
        $this->assertSame('from-settings', $config->getApiKey());
    }

    public function testKitPdfInputDependsOnModel(): void
    {
        $this->assertSame(AIProviderConfig::PDF_INPUT_NATIVE,
            AIProviderConfig::fromArray('kit', ['model' => 'azure.gpt-5.4'])->getPdfInputMode());
        $this->assertSame(AIProviderConfig::PDF_INPUT_NATIVE,
            AIProviderConfig::fromArray('kit', ['model' => 'google.gemini-3.5-flash'])->getPdfInputMode());
        $this->assertSame(AIProviderConfig::PDF_INPUT_TEXT,
            AIProviderConfig::fromArray('kit', ['model' => 'kit.mistral-small-4-119b-a8b'])->getPdfInputMode());
        $this->assertSame(AIProviderConfig::PDF_INPUT_TEXT,
            AIProviderConfig::fromArray('kit', ['model' => 'alias.complex-local'])->getPdfInputMode());
    }

    public function testExplicitPdfInputOverridesAuto(): void
    {
        $config = AIProviderConfig::fromArray('kit', ['model' => 'azure.gpt-5.4', 'pdfInput' => 'text']);
        $this->assertSame(AIProviderConfig::PDF_INPUT_TEXT, $config->getPdfInputMode());
    }

    public function testOpenAICompatibleNeedsModelButNoKey(): void
    {
        $config = AIProviderConfig::fromArray('openai-compatible', [
            'baseUrl' => 'http://vllm.local:8000/v1/', 'model' => 'qwen3-32b',
        ]);
        $this->assertSame('http://vllm.local:8000/v1', $config->getBaseUrl());
        $this->assertFalse($config->requiresApiKey());
        $this->assertNull($config->getApiKey());
        $this->assertSame(AIProviderConfig::PDF_INPUT_TEXT, $config->getPdfInputMode());

        $this->expectException(InvalidArgumentException::class);
        AIProviderConfig::fromArray('openai-compatible');
    }

    public function testPerProviderOverridesFromGlobals(): void
    {
        $GLOBALS['wgCEAIProvider'] = 'openai-compatible';
        $GLOBALS['wgCEAIProviders'] = ['openai-compatible' => [
            'baseUrl' => 'http://localhost:8000/v1', 'model' => 'llama', 'timeout' => 30, 'maxRetries' => 0,
        ]];

        $config = AIProviderConfig::fromGlobals();

        $this->assertSame('llama', $config->getModel());
        $this->assertSame(30, $config->getTimeout());
        $this->assertSame(0, $config->getMaxRetries());
    }

    public function testTopLevelModelOnlyAppliesToConfiguredProvider(): void
    {
        $GLOBALS['wgCEAIProvider'] = 'kit';
        $GLOBALS['wgCEAIModel'] = 'kit.deepseek-v4-flash';
        $GLOBALS['wgCEAIReasoningEffort'] = 'high';

        $this->assertSame('kit.deepseek-v4-flash', AIProviderConfig::fromGlobals()->getModel());
        $this->assertSame('high', AIProviderConfig::fromGlobals()->getReasoningEffort());

        // switching provider for one run must not inherit the KIT model name
        $openai = AIProviderConfig::fromGlobals('openai');
        $this->assertSame('o3', $openai->getModel());
        $this->assertNull($openai->getReasoningEffort());

        $this->assertSame('azure.gpt-5', AIProviderConfig::fromGlobals(null, 'azure.gpt-5')->getModel());
    }

    public function testUnknownProviderIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        AIProviderConfig::fromArray('anthropic-direct');
    }

    public function testInvalidReasoningEffortIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        AIProviderConfig::fromArray('kit', ['reasoningEffort' => 'max']);
    }

    public function testInvalidPdfInputIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        AIProviderConfig::fromArray('kit', ['pdfInput' => 'ocr']);
    }
}
