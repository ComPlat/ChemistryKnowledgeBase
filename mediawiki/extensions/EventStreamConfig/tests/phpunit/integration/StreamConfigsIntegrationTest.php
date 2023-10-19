<?php

namespace MediaWiki\Extension\EventStreamConfig;

use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\EventStreamConfig\StreamConfigs
 * @group EventStreamConfig
 */
class StreamConfigsIntegrationTest extends MediaWikiIntegrationTestCase {

	private const STREAM_CONFIGS_FIXTURE = [
		[
			'stream' => 'nonya',
			'schema_title' => 'mediawiki/nonya',
			'sample_rate' => 0.5,
			'EventServiceName' => 'eventgate-analytics',
		],
		[
			'stream' => 'test.event',
			'schema_title' => 'test/event',
			'sample_rate' => 1.0,
			'EventServiceName' => 'eventgate-main',
			'topic_prefixes' => [ 'eqiad.', 'codfw.' ],
		],
		[
			'stream' => '/^mediawiki\.job\..+/',
			'schema_title' => 'mediawiki/job',
			'sample_rate' => 0.8,
			'EventServiceName' => 'eventgate-main',
		],
	];

	private const STREAM_CONFIG_DEFAULT_SETTINGS_FIXTURE = [
		'topic_prefixes' => [ 'eqiad.', 'codfw.' ]
	];

	/**
	 * @covers MediaWiki\Extension\EventStreamConfig\StreamConfigs::__construct()
	 */
	public function testMediaWikiServiceIntegration() {
		$this->setMwGlobals( [
			'wgEventStreams' => self::STREAM_CONFIGS_FIXTURE,
			'wgEventStreamsDefaultSettings' => self::STREAM_CONFIG_DEFAULT_SETTINGS_FIXTURE,
		] );

		$streamConfigs = MediaWikiServices::getInstance()->getService(
			'EventStreamConfig.StreamConfigs'
		);

		$expected = [
			'nonya' => [
				'sample_rate' => 0.5,
			]
		];
		$result = $streamConfigs->get( [ 'nonya' ] );
		$this->assertEquals( $expected, $result );
	}
}
