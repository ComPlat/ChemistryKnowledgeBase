<?php

namespace DIQA\ChemExtension\PublicationImport;

use Exception;

/**
 * Creates the {@see AIClientInterface} for the configured provider (see
 * {@see AIProviderConfig} for the settings).
 */
final class AIClientFactory
{
    /**
     * @param AIProviderConfig|null $config null = read from LocalSettings ($wgCEAIProvider, ...)
     * @throws Exception if the provider is unknown or its API key is missing
     */
    public static function create(?AIProviderConfig $config = null): AIClientInterface
    {
        if (($GLOBALS['wgCEUseAIClientMock'] ?? false) === true) {
            return new AIClientMock();
        }
        $config = $config ?? AIProviderConfig::fromGlobals();

        if ($config->getProvider() === AIProviderConfig::OPENAI) {
            return new AIClient($config);
        }
        if ($config->requiresApiKey() && $config->getApiKey() === null) {
            throw new Exception(sprintf(
                "API key for AI provider '%s' is missing. Set \$wgCEAIProviders['%s']['apiKey'] "
                . "or the environment variable %s.",
                $config->getProvider(), $config->getProvider(),
                $config->getProvider() === AIProviderConfig::KIT ? 'KIT_TOOLBOX_API_KEY' : 'CE_AI_API_KEY'
            ));
        }
        return new ChatCompletionsAIClient($config);
    }
}
