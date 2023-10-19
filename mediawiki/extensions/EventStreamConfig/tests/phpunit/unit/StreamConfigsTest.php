<?php

namespace MediaWiki\Extension\EventStreamConfig;

use MediaWiki\Config\ServiceOptions;
use MediaWikiUnitTestCase;
use Psr\Log\NullLogger;

/**
 * @covers \MediaWiki\Extension\EventStreamConfig\StreamConfigs
 * @group EventStreamConfig
 */
class StreamConfigsTest extends MediaWikiUnitTestCase {

	private const STREAM_CONFIGS_FIXTURE = [
		[
			'stream' => 'nonya',
			'schema_title' => 'mediawiki/nonya',
			'sample_rate' => 0.5,
			'EventServiceName' => 'eventgate-analytics',
			// explicit topics
			'topics' => [ 'nonya_topic' ]
		],
		[
			'stream' => 'eventlogging_Test',
			'schema_title' => 'analytics/legacy/test',
			'sample_rate' => 1.0,
			'EventServiceName' => 'eventgate-analytics',
			// does not use topic prefixes, topic will be stream name
			'topic_prefixes' => null,
		],
		[
			'stream' => 'test.event',
			'schema_title' => 'test/event',
			'sample_rate' => 1.0,
			'EventServiceName' => 'eventgate-main',
			// overridden topic prefixes, should not use defaults
			'topic_prefixes' => [ 'dc1.', 'dc2.' ],
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

	public function setUp() : void {
		parent::setUp();
		$options = new ServiceOptions(
			StreamConfigs::CONSTRUCTOR_OPTIONS,
			[
				'EventStreams' => self::STREAM_CONFIGS_FIXTURE,
				'EventStreamsDefaultSettings' => self::STREAM_CONFIG_DEFAULT_SETTINGS_FIXTURE
			]
		);
		$logger = new NullLogger();

		// Use $this->streamConfigs in (most) tests below.
		$this->streamConfigs = new StreamConfigs( $options, $logger );
	}

	public function streamConfigsGetProvider() {
		return [

			[
				// targetStreams
				[ 'nonya' ],
				// allSettings
				false,
				// constrains
				null,
				// expected
				[
					'nonya' => [
						'sample_rate' => 0.5,
					]
				],
				// test message
				'get by specific stream'
			],

			[
				[ 'nonya', 'test.event' ],
				false,
				null,
				[
					'nonya' => [
						'sample_rate' => 0.5,
					],
					'test.event' => [
						'sample_rate' => 1.0,
					]
				],
				'get by specific streams'
			],

			[
				[ 'nonya', 'mediawiki.job.A', 'mediawiki.job.B' ],
				false,
				null,
				[
					'nonya' => [
						'sample_rate' => 0.5,
					],
					'mediawiki.job.A' => [
						'sample_rate' => 0.8,
					],
					'mediawiki.job.B' => [
						'sample_rate' => 0.8,
					]
				],
				'get by regex streams'
			],

			[
				[ 'nonya', 'mediawiki.job.workworkwork' ],
				true,
				null,
				[
					'nonya' => [
						'stream' => 'nonya',
						'schema_title' => 'mediawiki/nonya',
						'sample_rate' => 0.5,
						'EventServiceName' => 'eventgate-analytics',
						'topic_prefixes' => [ 'eqiad.', 'codfw.' ],
						'topics' => [ 'nonya_topic' ],
					],
					'mediawiki.job.workworkwork' => [
						'stream' => '/^mediawiki\.job\..+/',
						'schema_title' => 'mediawiki/job',
						'sample_rate' => 0.8,
						'EventServiceName' => 'eventgate-main',
						'topic_prefixes' => [ 'eqiad.', 'codfw.' ],
						'topics' => [
							'eqiad.mediawiki.job.workworkwork',
							'codfw.mediawiki.job.workworkwork'
						],
					]
				],
				'get by regex streams with all settings'
			],

			[
				null,
				true,
				null,
				[
					'nonya' => [
						'stream' => 'nonya',
						'schema_title' => 'mediawiki/nonya',
						'sample_rate' => 0.5,
						'EventServiceName' => 'eventgate-analytics',
						'topic_prefixes' => [ 'eqiad.', 'codfw.' ],
						'topics' => [ 'nonya_topic' ],
					],
					'eventlogging_Test' => [
						'stream' => 'eventlogging_Test',
						'schema_title' => 'analytics/legacy/test',
						'sample_rate' => 1.0,
						'EventServiceName' => 'eventgate-analytics',
						// does not use topic prefixes, topic will be stream name
						'topic_prefixes' => null,
						'topics' => [ 'eventlogging_Test' ],
					],
					'test.event' => [
						'stream' => 'test.event',
						'schema_title' => 'test/event',
						'sample_rate' => 1.0,
						'EventServiceName' => 'eventgate-main',
						'topic_prefixes' => [ 'dc1.', 'dc2.' ],
						'topics' => [ 'dc1.test.event', 'dc2.test.event' ],
					],
					// Since we aren't asking for a specific stream,
					// we will get this config keyed by its regex stream,
					// pattern rather than a specific stream name.
					'/^mediawiki\.job\..+/' => [
						'stream' => '/^mediawiki\.job\..+/',
						'schema_title' => 'mediawiki/job',
						'sample_rate' => 0.8,
						'EventServiceName' => 'eventgate-main',
						'topic_prefixes' => [ 'eqiad.', 'codfw.' ],
						'topics' => [ '/^(eqiad\.|codfw\.)mediawiki\.job\..+/' ],
					],
				],
				'get all streams with all settings'
			],

			[
				[ 'unconfigured-stream-name' ],
				false,
				null,
				[],
				'get an unconfigured stream name'
			],

			[
				null,
				true,
				[
					'EventServiceName' => 'eventgate-main',
				],
				[
					'test.event' => [
						'stream' => 'test.event',
						'schema_title' => 'test/event',
						'sample_rate' => 1.0,
						'EventServiceName' => 'eventgate-main',
						'topic_prefixes' => [ 'dc1.', 'dc2.' ],
						'topics' => [ 'dc1.test.event', 'dc2.test.event' ],
					],
					// Since we aren't asking for any specific streams,
					// we will get this config keyed by its regex stream,
					// pattern rather than a specific stream name.
					'/^mediawiki\.job\..+/' => [
						'stream' => '/^mediawiki\.job\..+/',
						'schema_title' => 'mediawiki/job',
						'sample_rate' => 0.8,
						'EventServiceName' => 'eventgate-main',
						'topic_prefixes' => [ 'eqiad.', 'codfw.' ],
						'topics' => [ '/^(eqiad\.|codfw\.)mediawiki\.job\..+/' ],
					]
				],
				'get all streams that have matching constraints'
			],

			[
				[ 'mediawiki.job.workworkwork' ],
				true,
				[
					'EventServiceName' => 'eventgate-main',
				],
				[
					'mediawiki.job.workworkwork' => [
						'stream' => '/^mediawiki\.job\..+/',
						'schema_title' => 'mediawiki/job',
						'sample_rate' => 0.8,
						'EventServiceName' => 'eventgate-main',
						'topic_prefixes' => [ 'eqiad.', 'codfw.' ],
						'topics' => [
							'eqiad.mediawiki.job.workworkwork',
							'codfw.mediawiki.job.workworkwork'
						],
					]
				],
				'get all streams that have matching stream names and constraints'
			],

			[
				[ 'nonya', 'mediawiki.job.workworkwork' ],
				true,
				null,
				[
					'nonya' => [
						'stream' => 'nonya',
						'schema_title' => 'mediawiki/nonya',
						'sample_rate' => 0.5,
						'EventServiceName' => 'eventgate-analytics',
						'topic_prefixes' => [ 'eqiad.', 'codfw.' ],
						'topics' => [ 'nonya_topic' ],
					],
					'mediawiki.job.workworkwork' => [
						'stream' => '/^mediawiki\.job\..+/',
						'schema_title' => 'mediawiki/job',
						'sample_rate' => 0.8,
						'EventServiceName' => 'eventgate-main',
						'topic_prefixes' => [ 'eqiad.', 'codfw.' ],
						'topics' => [
							'eqiad.mediawiki.job.workworkwork',
							'codfw.mediawiki.job.workworkwork'
						],
					]
				],
				'get by regex streams with all settings with topic prefixes'
			],
		];
	}

	/**
	 * @dataProvider streamConfigsGetProvider
	 */
	public function testGet(
		$targetStreams,
		$allSettings,
		$constraints,
		$expected,
		$message
	) {
		$result = $this->streamConfigs->get( $targetStreams, $allSettings, $constraints );
		$this->assertEquals( $expected, $result, $message );
	}

}
