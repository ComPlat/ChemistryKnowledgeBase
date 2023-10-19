<?php

namespace MediaWiki\Extension\EventStreamConfig;

use MediaWiki\Config\ServiceOptions;
use Psr\Log\LoggerInterface;
use Wikimedia\Assert\Assert;

/**
 * Functions to aid in exporting event stream configs.  These configs should be set
 * in Global MW config to allow for more dynamic configuration of event stream settings
 * e.g. sample rates or EventServiceName.
 *
 * Some terms:
 * - StreamConfigs    - List of individual Stream Configs
 * - A StreamConfig   - an object of event stream settings
 * - A stream setting - an individual setting for an stream, e.g. 'sample_rate'.
 *
 * See also:
 * - https://phabricator.wikimedia.org/T205319
 * - https://phabricator.wikimedia.org/T233634
 *
 * This expects that 'EventStreams' is set in MW Config to an array
 * of stream configs..  Each stream config entry should look something like:
 * [
 *      "stream" => "my.event.stream-name",
 *      "schema_title" => "my/event/schema",
 *      "sample_rate" => 0.8,
 *      "EventServiceName" => "eventgate-analytics-public",
 *      ...
 * ]
 *
 * `stream` may be a regex, in which case the functions here will match requested
 * target streams against the config stream name regex.
 */
class StreamConfigs {
	/**
	 * Name of the main config key(s) for stream configuration.
	 * @var array
	 */
	public const CONSTRUCTOR_OPTIONS = [
		'EventStreams',
		'EventStreamsDefaultSettings'
	];

	/**
	 * List of StreamConfig instances
	 * @var array
	 */
	private $streamConfigEntries = [];

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	private $logger;

	/**
	 * Constructs a new StreamConfigs instance initialized
	 * from wgEventStreams and wgEventStreamsDefaultSettings
	 *
	 * @param ServiceOptions $options
	 * @param LoggerInterface $logger
	 */
	public function __construct( ServiceOptions $options, LoggerInterface $logger ) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );

		$streamConfigsArray = $options->get( 'EventStreams' );
		Assert::parameterType( 'array', $streamConfigsArray, 'EventStreams' );

		$defaultSettings = $options->get( 'EventStreamsDefaultSettings' );
		Assert::parameterType( 'array', $defaultSettings, 'EventStreamsDefaultSettings' );

		foreach ( $streamConfigsArray as $streamConfig ) {
			$this->streamConfigEntries[] = new StreamConfig( $streamConfig, $defaultSettings );
		}

		$this->logger = $logger;
	}

	/**
	 * Looks for target stream names and returns matched stream configs keyed by stream name.
	 *
	 * @param array|null $targetStreams
	 *     List of stream names. If not provided, all stream configs will be returned.
	 * @param bool $includeAllSettings
	 *     If $includeAllSettings is false, only setting keys that match those in
	 *     StreamConfig::SETTINGS_FOR_EXPORT will be returned.
	 * @param array|null $settingsConstraints
	 *     If given, returned stream config entries will be filtered for those that
	 *     have these settings.
	 *
	 * @return array
	 */
	public function get(
		array $targetStreams = null,
		$includeAllSettings = false,
		array $settingsConstraints = null
	): array {
		$result = [];
		foreach ( $this->selectByStreams( $targetStreams ) as $stream => $streamConfigEntry ) {
			if (
				!$settingsConstraints ||
				$streamConfigEntry->matchesSettings( $settingsConstraints )
			) {
				$result[$stream] = $streamConfigEntry->toArray( $includeAllSettings, $stream );
			}
		}
		return $result;
	}

	/**
	 * Filter for stream names that match streams in $targetStreamNames.
	 *
	 * @param array|null $targetStreams
	 *     If not provided, all $streamConfigs will be returned, keyed by 'stream'.
	 *
	 * @return StreamConfig[]
	 */
	private function selectByStreams( array $targetStreams = null ): array {
		$groupedStreamConfigs = [];
		// If no $targetStreams were specified, then assume all are desired.
		if ( $targetStreams === null ) {
			// If no target stream names, just return the all stream config entries
			// but keyed by stream name.
			$this->logger->debug( 'Selecting all stream configs.' );

			foreach ( $this->streamConfigEntries as $streamConfigEntry ) {
				$groupedStreamConfigs[$streamConfigEntry->stream()] = $streamConfigEntry;
			}
		} else {
			$this->logger->debug(
				'Selecting stream configs for target streams', $targetStreams
			);

			foreach ( $targetStreams as $stream ) {
				// Find the config for this $stream.
				// configured stream names can be exact streams or regexes.
				// $stream will be matched against either.
				$streamConfigEntry = $this->findByStream( $stream );

				if ( $streamConfigEntry === null ) {
					$this->logger->warning(
						"Stream '$stream' does not match any `stream` in stream config"
					);
				} else {
					// Else include the settings in the stream config result..
					$groupedStreamConfigs[$stream] = $streamConfigEntry;
				}
			}
		}

		return $groupedStreamConfigs;
	}

	/**
	 * Given a $stream name to get, this matches $stream against
	 * `stream` in $streamConfigs and returns the first found StreamConfig object.
	 * If no match is found, returns null.
	 *
	 * @param string $stream
	 * @return StreamConfig|null
	 */
	private function findByStream( $stream ) {
		// Find the first 'stream' in $streamConfigs that matches $streamName.
		foreach ( $this->streamConfigEntries as $streamConfig ) {
			if ( $streamConfig->matches( $stream ) ) {
				return $streamConfig;
			}
		}

		return null;
	}

}
