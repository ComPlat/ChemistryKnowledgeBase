( function () {
	'use strict';

	var originalVE;
	// Step specification as passed to the legacy defineTour method
	var VALID_DEFINE_TOUR_STEP_SPEC = {
		titlemsg: 'guidedtour-tour-test-callouts',
		descriptionmsg: 'guidedtour-tour-test-portal-description',
		attachTo: '#n-portal a',
		position: '3',
		buttons: [ {
			action: 'next'
		} ]
	};
	// Step specification as used with the current builder API
	var VALID_BUILDER_STEP_SPEC = {
		name: 'intro',
		titlemsg: 'guidedtour-tour-test-intro-title',
		descriptionmsg: 'guidedtour-tour-test-intro-description',
		position: 'bottom',
		attachTo: '#ca-edit'
	};
	var validTourBuilder, validTour, firstStepBuilder, firstStep, otherTourBuilder, otherTourStepBuilder;

	var gt = mw.guidedTour;
	var cookieConfig = gt.getCookieConfiguration();
	var cookieName = cookieConfig.name;
	var cookieParams = cookieConfig.parameters;

	function compareTypeAndMessage( errorConstructor, regexErrorMessage ) {
		return function ( actualException ) {
			return actualException instanceof errorConstructor &&
				regexErrorMessage.test( actualException );
		};
	}

	QUnit.module( 'ext.guidedTour.lib', QUnit.newMwEnvironment( {
		beforeEach: function () {
			originalVE = window.ve;

			validTourBuilder = new gt.TourBuilder( { name: 'placeholder' } );
			validTour = validTourBuilder.tour;
			firstStepBuilder = validTourBuilder.firstStep( VALID_BUILDER_STEP_SPEC );
			firstStep = firstStepBuilder.step;

			otherTourBuilder = new gt.TourBuilder( {
				name: 'upload'
			} );
			otherTourStepBuilder = otherTourBuilder.step( {
				name: 'filename',
				description: 'filename description'
			} );

			this.stub( mw.libs.guiders, 'show' );
		},
		afterEach: function () {
			window.ve = originalVE;
		}
	} ) );

	QUnit.test( 'makeTourId', function ( assert ) {
		assert.strictEqual(
			gt.makeTourId( {
				name: 'test',
				step: 3
			} ),
			'gt-test-3',
			'Successful makeTourId call'
		);

		assert.strictEqual(
			gt.makeTourId( 'test' ),
			null,
			'String input returns null'
		);

		assert.strictEqual(
			gt.makeTourId( null ),
			null,
			'null input returns null'
		);

		assert.strictEqual(
			gt.makeTourId(),
			null,
			'Missing parameter returns null'
		);
	} );

	QUnit.test( 'parseTourId', function ( assert ) {
		var tourId = 'gt-test-2', expectedTourInfo;
		expectedTourInfo = {
			name: 'test',
			step: '2'
		};
		assert.deepEqual(
			gt.parseTourId( tourId ),
			expectedTourInfo,
			'Simple tourId'
		);
	} );

	QUnit.test( 'isPage', function ( assert ) {
		var PAGE_NAME_TO_SKIP = 'TestPage',
			OTHER_PAGE_NAME = 'WrongPage';

		mw.config.set( 'wgPageName', PAGE_NAME_TO_SKIP );
		assert.strictEqual(
			gt.isPage( PAGE_NAME_TO_SKIP ),
			true,
			'Page matches'
		);

		mw.config.set( 'wgPageName', OTHER_PAGE_NAME );
		assert.strictEqual(
			gt.isPage( PAGE_NAME_TO_SKIP ),
			false,
			'Page does match'
		);
	} );

	QUnit.test( 'hasQuery', function ( assert ) {
		var paramMap,
			PAGE_NAME_TO_SKIP = 'RightPage',
			OTHER_PAGE_NAME = 'OtherPage';

		this.sandbox.stub( mw.util, 'getParamValue', function ( param ) {
			return paramMap[ param ];
		} );

		paramMap = { action: 'edit', debug: 'true' };

		assert.strictEqual(
			gt.hasQuery( { action: 'edit' } ),
			true,
			'Query matches, page name is undefined'
		);
		assert.strictEqual(
			gt.hasQuery( { action: 'edit' }, null ),
			true,
			'Query matches, page name is null'
		);

		mw.config.set( 'wgPageName', PAGE_NAME_TO_SKIP );
		assert.strictEqual(
			gt.hasQuery( { action: 'edit' }, PAGE_NAME_TO_SKIP ),
			true,
			'Query and page both match'
		);

		mw.config.set( 'wgPageName', OTHER_PAGE_NAME );
		assert.strictEqual(
			gt.hasQuery( { action: 'edit' }, PAGE_NAME_TO_SKIP ),
			false,
			'Query matches, but page does not' );

		paramMap = { debug: 'true', somethingElse: 'medium' };

		assert.strictEqual(
			gt.hasQuery( { action: 'edit' } ),
			false,
			'Query does not match, page is undefined'
		);

		mw.config.set( 'wgPageName', PAGE_NAME_TO_SKIP );
		assert.strictEqual(
			gt.hasQuery( { action: 'edit' }, PAGE_NAME_TO_SKIP ),
			false,
			'Query does not match, although page does'
		);

		mw.config.set( 'wgPageName', OTHER_PAGE_NAME );
		assert.strictEqual(
			gt.hasQuery( { action: 'edit' }, PAGE_NAME_TO_SKIP ),
			false,
			'Neither query nor page match'
		);
	} );

	QUnit.test( 'getStepFromQuery', function ( assert ) {
		var step;
		this.sandbox.stub( mw.util, 'getParamValue', function () {
			return step;
		} );

		step = 6;
		assert.strictEqual(
			gt.getStepFromQuery(),
			step,
			'Step is returned correctly when present'
		);

		step = null;
		assert.strictEqual(
			gt.getStepFromQuery(),
			step,
			'Step is returned as null when not present'
		);
	} );

	QUnit.test( 'setTourCookie', function ( assert ) {
		var firstTourName = 'foo';
		var secondTourName = 'bar';
		var numberStep = 5;
		var stringStep = '3';
		var oldCookieValue = mw.cookie.get( cookieName );

		function assertValidCookie( expectedName, expectedStep, message ) {
			var cookieValue = mw.cookie.get( cookieName );
			var userState = gt.internal.parseUserState( cookieValue );

			assert.strictEqual(
				userState.tours[ expectedName ].step,
				expectedStep,
				message
			);
		}

		function clearCookie() {
			mw.cookie.set( cookieName, null, cookieParams );
		}

		gt.setTourCookie( firstTourName );
		assertValidCookie( firstTourName, '1', 'Step defaults to 1' );
		clearCookie();

		gt.setTourCookie( firstTourName, numberStep );
		assertValidCookie( firstTourName, String( numberStep ), 'setTourCookie accepts numeric step, which is converted to string' );
		clearCookie();

		gt.setTourCookie( firstTourName, stringStep );
		assertValidCookie( firstTourName, stringStep, 'setTourCookie accepts string step' );

		gt.setTourCookie( secondTourName, numberStep );
		assertValidCookie( firstTourName, stringStep, 'First tour is still remembered after second is stored' );
		assertValidCookie( secondTourName, String( numberStep ), 'Second tour is also remembered' );

		mw.cookie.set( cookieName, oldCookieValue, cookieParams );
	} );

	QUnit.test( 'shouldShow', function ( assert ) {
		var visualEditorArgs = {
			tourName: 'visualeditorintro',
			userState: {
				version: 1,
				tours: {}
			},
			pageName: 'Page',
			articleId: 123,
			condition: 'VisualEditor'
		};

		var wikitextArgs = {
			tourName: 'wikitextintro',
			userState: {
				version: 1,
				tours: {}
			},
			pageName: 'Page',
			articleId: 123,
			condition: 'wikitext'
		};

		var mockOpenVE = {
			instances: [ {} ]
		};

		assert.throws(
			function () {
				return gt.shouldShowTour( {
					tourName: 'test',
					userState: {
						version: 1,
						tours: {
							test: {
								step: 1
							}
						}
					},
					pageName: 'Foo',
					articleId: 123,
					condition: 'bogus'
				} );
			},
			compareTypeAndMessage( gt.TourDefinitionError, /'bogus' is not a supported condition/ ),
			'gt.TourDefinitionError with correct error message for invalid condition'
		);

		assert.strictEqual(
			gt.shouldShowTour( {
				tourName: 'test',
				userState: {
					version: 1,
					tours: {
						test: {
							firstArticleId: 123,
							step: 1
						}
					}
				},
				pageName: 'Foo',
				articleId: 123,
				condition: 'stickToFirstPage'
			} ),
			true,
			'Returns true for stickToFirstPage when on the original article'
		);

		assert.strictEqual(
			gt.shouldShowTour( {
				tourName: 'test',
				userState: {
					version: 1,
					tours: {
						test: {
							firstArticleId: 123,
							step: 1
						}
					}
				},
				pageName: 'Foo',
				articleId: 987,
				condition: 'stickToFirstPage'
			} ),
			false,
			'Returns false for stickToFirstPage when on a different article'
		);

		assert.strictEqual(
			gt.shouldShowTour( {
				tourName: 'test',
				userState: {
					version: 1,
					tours: {
						test: {
							step: 1
						}
					}
				},
				pageName: 'Bar',
				articleId: 123
			} ),
			true,
			'Returns true when there is no condition'
		);

		assert.strictEqual(
			gt.shouldShowTour( {
				tourName: 'test',
				userState: {
					version: 1,
					tours: {
						test: {
							firstArticleId: 234,
							step: 1
						}
					}
				},
				pageName: 'Bar',
				articleId: 123
			} ),
			true,
			'Returns true when there is no condition even when there is a non-matching article ID in the cookie'
		);

		assert.strictEqual(
			gt.shouldShowTour( {
				tourName: 'test',
				userState: {
					version: 1,
					tours: {
						test: {},
						othertour: {
							firstArticleId: 234,
							step: 1
						}
					}
				},
				pageName: 'Bar',
				articleId: 123
			} ),
			true,
			'Returns true when there is no condition even when there is a non-matching article ID in the cookie, for another tour'
		);

		assert.strictEqual(
			gt.shouldShowTour( {
				tourName: 'test',
				userState: {
					version: 1,
					tours: {
						test: {
							firstSpecialPageName: 'Special:ImportantTask',
							step: 1
						}
					}
				},
				pageName: 'Special:ImportantTask',
				articleId: 0,
				condition: 'stickToFirstPage'
			} ),
			true,
			'Returns true for stickToFirstPage and matching special page'
		);

		assert.strictEqual(
			gt.shouldShowTour( {
				tourName: 'test',
				userState: {
					version: 1,
					tours: {
						test: {
							firstSpecialPageName: 'Special:ImportantTask',
							step: 1
						}
					}
				},
				pageName: 'Special:OtherTask',
				articleId: 0,
				condition: 'stickToFirstPage'
			} ),
			false,
			'Returns false for stickToFirstPage and different special page'
		);

		assert.strictEqual(
			gt.shouldShowTour( {
				tourName: 'secondtour',
				userState: {
					version: 1,
					tours: {
						firsttour: {
							firstArticleId: 123,
							step: 1
						},
						secondtour: {
							firstArticleId: 234,
							step: 2
						}
					}
				},
				pageName: 'Foo',
				articleId: 123,
				condition: 'stickToFirstPage'
			} ),
			false,
			'Returns false for stickToFirstPage for non-matching article ID when another tour\'s article ID matches'
		);

		// Mock the ve global, and its array of instances.
		window.ve = mockOpenVE;
		assert.strictEqual(
			gt.shouldShowTour( visualEditorArgs ),
			true,
			'Returns true for VisualEditor condition when VisualEditor open'
		);

		// ve = undefined deliberately applies to all of the below until it is
		// reset to a mock instance for the expected false text
		window.ve = undefined;
		mw.config.set( 'wgAction', 'view' );
		assert.strictEqual(
			gt.shouldShowTour( visualEditorArgs ),
			true,
			'Returns true for VisualEditor condition when viewing page with VE closed'
		);

		mw.config.set( 'wgAction', 'edit' );
		assert.strictEqual(
			gt.shouldShowTour( visualEditorArgs ),
			false,
			'Returns false for VisualEditor condition when in wikitext editor'
		);

		mw.config.set( 'wgAction', 'submit' );
		assert.strictEqual(
			gt.shouldShowTour( visualEditorArgs ),
			false,
			'Returns false for VisualEditor condition when reviewing wikitext changes'
		);

		mw.config.set( 'wgAction', 'edit' );
		assert.strictEqual(
			gt.shouldShowTour( wikitextArgs ),
			true,
			'Returns true for wikitext condition when editing wikitext'
		);

		mw.config.set( 'wgAction', 'submit' );
		assert.strictEqual(
			gt.shouldShowTour( wikitextArgs ),
			true,
			'Returns true for wikitext condition when reviewing wikitext'
		);

		mw.config.set( 'wgAction', 'view' );
		assert.strictEqual(
			gt.shouldShowTour( wikitextArgs ),
			true,
			'Returns true for wikitext condition when viewing page with VE closed'
		);

		window.ve = mockOpenVE;
		mw.config.set( 'wgAction', 'view' );
		assert.strictEqual(
			gt.shouldShowTour( wikitextArgs ),
			false,
			'Returns false for wikitext condition when VisualEditor is open'
		);

		assert.strictEqual(
			gt.shouldShowTour( {
				tourName: 'firsttour',
				userState: {
					version: 1,
					tours: {
						firsttour: {
							firstSpecialPageName: 'Special:ImportantTask',
							step: 1
						},
						secondtour: {
							firstSpecialPageName: 'Special:OtherTask',
							step: 2
						}
					}
				},
				pageName: 'Special:OtherTask',
				articleId: 0,
				condition: 'stickToFirstPage'
			} ),
			false,
			'Returns false for non-matching article ID when another tour\'s special page matches'
		);
	} );

	QUnit.test( 'defineTour', function ( assert ) {
		var SPEC_MUST_BE_OBJECT = /Check your syntax. There must be exactly one argument, 'tourSpec', which must be an object\./;
		var NAME_MUST_BE_STRING = /'tourSpec.name' must be a string, the tour name\./;
		var STEPS_MUST_BE_ARRAY = /'tourSpec.steps' must be an array, a list of one or more steps/;
		var VALID_TOUR_SPEC = {
			name: 'valid',

			steps: [ {
				title: 'First step title',
				description: 'Second step title',
				overlay: true,
				buttons: [ {
					action: 'next'
				} ]
			}, {
				title: 'Second step title',
				description: 'Second step description',
				overlay: true,
				buttons: [ {
					action: 'end'
				} ]
			} ]
		};

		// Suppress warnings that defineTour is deprecated
		this.suppressWarnings();

		assert.throws(
			function () {
				return gt.defineTour();
			},
			compareTypeAndMessage( gt.TourDefinitionError, SPEC_MUST_BE_OBJECT ),
			'gt.TourDefinitionError with correct error message for empty call'
		);

		assert.throws(
			function () {
				return gt.defineTour( VALID_TOUR_SPEC, VALID_DEFINE_TOUR_STEP_SPEC );
			},
			compareTypeAndMessage( gt.TourDefinitionError, SPEC_MUST_BE_OBJECT ),
			'gt.TourDefinitionError with correct error message for multiple parameters'
		);

		assert.throws(
			function () {
				return gt.defineTour( null );
			},
			compareTypeAndMessage( gt.TourDefinitionError, SPEC_MUST_BE_OBJECT ),
			'gt.TourDefinitionError with correct error message for null call'
		);

		assert.throws(
			function () {
				return gt.defineTour( {
					steps: [ VALID_DEFINE_TOUR_STEP_SPEC ]
				} );
			},
			compareTypeAndMessage( gt.TourDefinitionError, NAME_MUST_BE_STRING ),
			'gt.TourDefinitionError with correct error message for missing name'
		);

		assert.throws(
			function () {
				return gt.defineTour( {
					name: 'test',
					steps: VALID_DEFINE_TOUR_STEP_SPEC
				} );
			},
			compareTypeAndMessage( gt.TourDefinitionError, STEPS_MUST_BE_ARRAY ),
			'gt.TourDefinitionError with correct error message for object passed for steps'
		);

		assert.throws(
			function () {
				return gt.defineTour( {
					name: 'test'
				} );
			},
			compareTypeAndMessage( gt.TourDefinitionError, STEPS_MUST_BE_ARRAY ),
			'gt.TourDefinitionError with correct error message for missing steps'
		);

		assert.strictEqual(
			gt.defineTour( VALID_TOUR_SPEC ),
			true,
			'Valid tour is defined successfully'
		);

		this.restoreWarnings();
	} );

	QUnit.test( 'StepBuilder.constructor', function ( assert ) {
		var STEP_NAME_MUST_BE_STRING = /'stepSpec.name' must be a string, the step name/;

		assert.strictEqual(
			firstStepBuilder.constructor,
			gt.StepBuilder,
			'Valid StepBuilder constructed in setup is constructed normally'
		);

		assert.throws(
			function () {
				// eslint-disable-next-line no-unused-vars
				var missingNameBuilder = new gt.StepBuilder( validTour, {
					position: 'bottom',
					attachTo: '#ca-edit'
				} );
			},
			compareTypeAndMessage( gt.TourDefinitionError, STEP_NAME_MUST_BE_STRING ),
			'gt.TourDefinitionError when name is missing'
		);

		assert.throws(
			function () {
				// eslint-disable-next-line no-unused-vars
				var numericNameBuilder = new gt.StepBuilder( validTour, {
					name: 1,
					position: 'bottom',
					attachTo: '#ca-edit'
				} );
			},
			compareTypeAndMessage( gt.TourDefinitionError, STEP_NAME_MUST_BE_STRING ),
			'gt.TourDefinitionError when name is a Number'
		);
	} );

	QUnit.test( 'StepBuilder.listenForMwHooks', function ( assert ) {
		var listenForMwHookSpy = this.spy( firstStepBuilder.step, 'listenForMwHook' );

		firstStepBuilder.listenForMwHooks();
		assert.strictEqual(
			listenForMwHookSpy.callCount,
			0,
			'If no hook names are passed, step.listenForMwHook should not be called'
		);

		firstStepBuilder.listenForMwHooks( 'StepBuilder.listenForMwHooks.happened' );
		assert.strictEqual(
			listenForMwHookSpy.callCount,
			1,
			'step.listenMwHook should be called once if a single hook name is passed'
		);
		assert.assertTrue(
			listenForMwHookSpy.calledWithExactly( 'StepBuilder.listenForMwHooks.happened' ),
			'step.listenMwHook should be called once with the correct hook name if a single hook name is passed'
		);

		listenForMwHookSpy.reset();
		firstStepBuilder.listenForMwHooks( 'StepBuilder.listenForMwHooks.one', 'StepBuilder.listenForMwHooks.another' );
		assert.strictEqual(
			listenForMwHookSpy.callCount,
			2,
			'step.listenMwHook should be called twice if two hook names are passed'
		);
		assert.assertTrue(
			listenForMwHookSpy.calledWithExactly( 'StepBuilder.listenForMwHooks.one' ),
			'step.listenMwHook should be called with the first hook name if multiple are passed'
		);
		assert.assertTrue(
			listenForMwHookSpy.calledWithExactly( 'StepBuilder.listenForMwHooks.another' ),
			'step.listenMwHook should be called with the second hook name if multiple are passed'
		);
	} );

	QUnit.test( 'StepBuilder.next', function ( assert ) {
		var VALUE_PASSED_NEXT_NOT_VALID_STEP = /Value passed to \.next\(\) does not refer to a valid step/;
		var CALLBACK_PASSED_NEXT_RETURNED_INVALID = /Callback passed to \.next\(\) returned invalid value/;

		var saveStepBuilder;
		function stepBuilderCallback() {
			return saveStepBuilder;
		}

		var linkStepBuilder = validTourBuilder.step( {
			name: 'link',
			description: 'link description'
		} );

		var editStepBuilder = validTourBuilder.step( {
			name: 'edit',
			description: 'edit description'
		} );

		var previewStepBuilder = validTourBuilder.step( {
			name: 'preview',
			description: 'preview description'
		} );

		saveStepBuilder = validTourBuilder.step( {
			name: 'save',
			description: 'save description'
		} );

		var pointsInvalidNameStepBuilder = validTourBuilder.step( {
			name: 'returnsToInvalidName',
			description: 'returnsToInvalidName description'
		} );
		pointsInvalidNameStepBuilder.next( 'bogus' );
		assert.throws(
			function () {
				pointsInvalidNameStepBuilder.step.nextCallback();
			},
			compareTypeAndMessage( gt.TourDefinitionError, VALUE_PASSED_NEXT_NOT_VALID_STEP ),
			'nextCallback throws if an invalid (not present in current tour) step name was passed to next'
		);

		firstStepBuilder.next( 'link' );
		assert.strictEqual(
			firstStepBuilder.step.nextCallback(),
			linkStepBuilder.step,
			'Registers a callback that returns the correct Step, given a step name'
		);

		assert.throws(
			function () {
				firstStepBuilder.next( stepBuilderCallback );
			},
			compareTypeAndMessage( gt.TourDefinitionError, /\.next\(\) can not be called more than once per StepBuilder/ ),
			'Multiple calls should trigger an error'
		);

		var pointsOtherTourStepBuilder = validTourBuilder.step( {
			name: 'returnsToOtherTour',
			description: 'returnsToOtherTour description'
		} );
		pointsOtherTourStepBuilder.next( otherTourStepBuilder );
		assert.throws(
			function () {
				pointsOtherTourStepBuilder.step.nextCallback();
			},
			compareTypeAndMessage( gt.TourDefinitionError, VALUE_PASSED_NEXT_NOT_VALID_STEP ),
			'nextCallback throws if a StepBuilder from a different Tour was passed to next'
		);

		linkStepBuilder.next( editStepBuilder );
		assert.strictEqual(
			linkStepBuilder.step.nextCallback(),
			editStepBuilder.step,
			'Registers a callback that returns the correct Step, given a StepBuilder'
		);

		var returnsInvalidNameCallbackStepBuilder = validTourBuilder.step( {
			name: 'returnsInvalidNameCallback',
			description: 'returnsInvalidNameCallback description'
		} );
		returnsInvalidNameCallbackStepBuilder.next( function () {
			return 'bogus';
		} );
		assert.throws(
			function () {
				returnsInvalidNameCallbackStepBuilder.step.nextCallback();
			},
			compareTypeAndMessage( gt.TourDefinitionError, CALLBACK_PASSED_NEXT_RETURNED_INVALID ),
			'nextCallback throws if a callback that returns an invalid step name was passed to next'
		);

		editStepBuilder.next( function () {
			return 'preview';
		} );
		assert.strictEqual(
			editStepBuilder.step.nextCallback(),
			previewStepBuilder.step,
			'Registers a callback that returns the correct Step, given a callback returning a step name'
		);

		var returnsOtherTourCallbackStepBuilder = validTourBuilder.step( {
			name: 'returnsToOtherTourCallback',
			description: 'returnsToOtherTourCallback description'
		} );
		returnsOtherTourCallbackStepBuilder.next( function () {
			return otherTourStepBuilder;
		} );
		assert.throws(
			function () {
				returnsOtherTourCallbackStepBuilder.step.nextCallback();
			},
			compareTypeAndMessage( gt.TourDefinitionError, CALLBACK_PASSED_NEXT_RETURNED_INVALID ),
			'nextCallback throws if a callback that returns a StepBuilder from a different Tour was passed to next'
		);

		previewStepBuilder.next( stepBuilderCallback );
		assert.strictEqual(
			previewStepBuilder.step.nextCallback(),
			saveStepBuilder.step,
			'Registers a callback that returns the correct Step, given a callback returning a StepBuilder'
		);
	} );

	QUnit.test( 'StepBuilder.transition', function ( assert ) {
		var CALLBACK_PASSED_TRANSITION_RETURNED_INVALID = /Callback passed to \.transition\(\) returned invalid value/;

		var linkStepBuilder = validTourBuilder.step( {
			name: 'link',
			description: 'link description'
		} );
		firstStepBuilder.transition( function () {
			return linkStepBuilder;
		} );
		assert.strictEqual(
			firstStepBuilder.step.transitionCallback(),
			linkStepBuilder.step,
			'Registers a callback that returns the correct Step, given a callback returning a StepBuilder'
		);

		var editStepBuilder = validTourBuilder.step( {
			name: 'edit',
			description: 'edit description'
		} );
		linkStepBuilder.transition( function () {
			return 'edit';
		} );
		assert.strictEqual(
			linkStepBuilder.step.transitionCallback(),
			editStepBuilder.step,
			'Registers a callback that returns the correct Step, given a callback returning a step name'
		);

		editStepBuilder.transition( function () {
			return gt.TransitionAction.HIDE;
		} );
		assert.strictEqual(
			editStepBuilder.step.transitionCallback(),
			gt.TransitionAction.HIDE,
			'Valid TransitionAction (HIDE) is preserved'
		);

		var previewStepBuilder = validTourBuilder.step( {
			name: 'preview',
			description: 'preview description'
		} );
		previewStepBuilder.transition( function () {} );
		assert.strictEqual(
			previewStepBuilder.step.transitionCallback(),
			previewStepBuilder.step,
			'Callback without an explicit return value is treated as returning the current step'
		);

		var returnsInvalidNameCallbackStepBuilder = validTourBuilder.step( {
			name: 'returnsInvalidNameCallback',
			description: 'returnsInvalidNameCallback description'
		} );
		returnsInvalidNameCallbackStepBuilder.transition( function () {
			return 'bogus';
		} );
		assert.throws(
			function () {
				returnsInvalidNameCallbackStepBuilder.step.transitionCallback();
			},
			compareTypeAndMessage( gt.TourDefinitionError, CALLBACK_PASSED_TRANSITION_RETURNED_INVALID ),
			'transitionCallback throws if a callback that returns an invalid step name was passed to transition'
		);

		var returnsOtherTourCallbackStepBuilder = validTourBuilder.step( {
			name: 'returnsToOtherTourCallback',
			description: 'returnsToOtherTourCallback description'
		} );
		returnsOtherTourCallbackStepBuilder.transition( function () {
			return otherTourStepBuilder;
		} );
		assert.throws(
			function () {
				returnsOtherTourCallbackStepBuilder.step.transitionCallback();
			},
			compareTypeAndMessage( gt.TourDefinitionError, CALLBACK_PASSED_TRANSITION_RETURNED_INVALID ),
			'transitionCallback throws if a callback that returns a StepBuilder from a different Tour was passed to transition'
		);

		var returnsInvalidTransitionActionStepBuilder = validTourBuilder.step( {
			name: 'returnsInvalidTransitionAction',
			description: 'returnsInvalidTransitionAction description'
		} );
		returnsInvalidTransitionActionStepBuilder.transition( function () {
			return 3;
		} );
		assert.throws(
			function () {
				returnsInvalidTransitionActionStepBuilder.step.transitionCallback();
			},
			compareTypeAndMessage( gt.TourDefinitionError, /Callback passed to \.transition\(\) returned a number that is not a valid TransitionAction/ ),
			'transitionCallback throws if a callback returns a number that is not a valid TransitionAction'
		);

		assert.throws(
			function () {
				firstStepBuilder.transition( function () {
					return editStepBuilder;
				} );
			},
			compareTypeAndMessage( gt.TourDefinitionError, /\.transition\(\) can not be called more than once per StepBuilder/ ),
			'Multiple calls should trigger an error'
		);

		var parameterNotFunctionStepBuilder = validTourBuilder.step( {
			name: 'parameterNotFunctionStepBuilder',
			description: 'parameterNotFunctionStepBuilder description'
		} );
		assert.throws(
			function () {
				parameterNotFunctionStepBuilder.transition( linkStepBuilder );
			},
			compareTypeAndMessage( gt.TourDefinitionError, /\.transition\(\) takes one argument, a function/ ),
			'callback is not a function'
		);
	} );

	QUnit.test( 'Step.constructor', function ( assert ) {
		var step = new gt.Step( validTour, {
			name: 'first',
			description: 'first description'
		} );

		assert.strictEqual(
			step.tour,
			validTour,
			'Step is associated with its tour'
		);

		assert.strictEqual(
			step.specification.id,
			'gt-placeholder-first',
			'Step ID is correct'
		);

		assert.throws(
			function () {
				step.nextCallback();
			},
			compareTypeAndMessage( gt.TourDefinitionError, /action: "next" used without calling \.next\(\) when building step/ ),
			'Error is flagged if Step is constructed, the nextCallback is used without calling .next() on builder'
		);

		assert.strictEqual(
			step.transitionCallback(),
			step,
			'By default, the transition callback returns the step it was called on'
		);
	} );

	QUnit.test( 'Step.getButtons', function ( assert ) {
		var buttons = [
			{ type: 'destructive' },
			{ type: [ 'progressive', 'quiet' ] },
			{ action: 'wikiLink', type: 'progressive' },
			{ action: 'externalLink' },
			{ action: 'back' },
			{ action: 'okay', onclick: function () {} },
			{ action: 'next' }
		];
		var spy = this.spy( gt.Step.prototype, 'getButtons' );
		var tourBuilder = new gt.TourBuilder( { name: 'buttonsTest' } );
		var firstStepBuilder = tourBuilder.firstStep( $.extend( true, {}, { buttons: buttons }, VALID_BUILDER_STEP_SPEC ) );
		var firstStep = firstStepBuilder.step;

		tourBuilder.tour.showStep( firstStep );
		var returnedButtons = spy.lastCall.args[ 0 ].buttons;
		assert.ok(
			returnedButtons[ 0 ].html.class.indexOf( 'mw-ui-destructive' ) !== -1 &&
			returnedButtons[ 0 ].html.class.indexOf( 'mw-ui-button' ) !== -1,
			'Destructive custom button'
		);
		assert.ok(
			returnedButtons[ 1 ].html.class.indexOf( 'mw-ui-button' ) !== -1 &&
			returnedButtons[ 1 ].html.class.indexOf( 'mw-ui-progressive' ) !== -1 &&
			returnedButtons[ 1 ].html.class.indexOf( 'mw-ui-quiet' ) !== -1,
			'A quietly progressive custom button'
		);
		assert.notStrictEqual(
			returnedButtons[ 2 ].html.class.indexOf( 'mw-ui-progressive' ),
			-1,
			'Progressive internal link'
		);
		assert.strictEqual(
			returnedButtons[ 3 ].html.class.indexOf( 'mw-ui-progressive' ),
			-1,
			'External link button is not progressive by default'
		);
		assert.strictEqual(
			returnedButtons[ 4 ].html.class.indexOf( 'mw-ui-progressive' ),
			-1,
			'Back button is not progressive by default'
		);
		assert.notStrictEqual(
			returnedButtons[ 5 ].html.class.indexOf( 'mw-ui-progressive' ),
			-1,
			'Okay button is progressive by default'
		);
		assert.notStrictEqual(
			returnedButtons[ 6 ].html.class.indexOf( 'mw-ui-progressive' ),
			-1,
			'Next button is progressive by default'
		);
	} );

	QUnit.test( 'Step.registerMwHookListener', function ( assert ) {
		var step = firstStepBuilder.step,
			HOOK_NAME = 'Step.registerMwHookListener.happened',
			// This lets us verify checkTransition is called (and which
			// arguments), but ignore any further behavior (e.g. showing
			// a step and TRANSITION_BEFORE_SHOW)
			checkTransitionStub, actualTransitionEvent, expectedTransitionEvent;

		checkTransitionStub = this.stub( step, 'checkTransition' ).returns( null );

		mw.hook( HOOK_NAME ).fire( 'first', 1 );
		step.registerMwHookListener( HOOK_NAME );

		assert.strictEqual(
			checkTransitionStub.callCount,
			0,
			'Memory firing should be ignored'
		);

		mw.hook( HOOK_NAME ).fire( 'second', 2 );
		assert.strictEqual(
			checkTransitionStub.callCount,
			1,
			'checkTransition should be called exactly once when there is a single mw.hook firing'
		);

		actualTransitionEvent = checkTransitionStub.lastCall.args[ 0 ];
		expectedTransitionEvent = new gt.TransitionEvent();
		expectedTransitionEvent.type = gt.TransitionEvent.MW_HOOK;
		expectedTransitionEvent.hookName = HOOK_NAME;
		expectedTransitionEvent.hookArguments = [ 'second', 2 ];

		assert.deepEqual(
			actualTransitionEvent,
			expectedTransitionEvent,
			'checkTransition should be called with the right TransitionEvent'
		);

		checkTransitionStub.reset();
		mw.hook( 'Step.registerMwHookListener.otherHook' ).fire( 'third', 3 );
		assert.strictEqual(
			checkTransitionStub.callCount,
			0,
			'checkTransition should not be called for hooks that were not registered'
		);
	} );

	QUnit.test( 'Step.registerMwHooks', function ( assert ) {
		var step = firstStepBuilder.step,
			registerMwHookListenerSpy;
		registerMwHookListenerSpy = this.spy( step, 'registerMwHookListener' );

		step.listenForMwHook( 'Step.registerMwHooks.something' );
		step.listenForMwHook( 'Step.registerMwHooks.another' );

		step.registerMwHooks();
		assert.strictEqual(
			registerMwHookListenerSpy.callCount,
			2,
			'registerMwHookListener called once for each hook the step is listening for'
		);

		assert.assertTrue(
			registerMwHookListenerSpy.calledWithExactly( 'Step.registerMwHooks.something' ),
			'registerMwHookListener called with the first hook that is being listened for'
		);

		assert.assertTrue(
			registerMwHookListenerSpy.calledWithExactly( 'Step.registerMwHooks.another' ),
			'registerMwHookListener called with the second hook that is being listened for'
		);
	} );

	QUnit.test( 'Step.handleOnShow', function ( assert ) {
		var showChangesStepBuilder = validTourBuilder.step( {
			name: 'showChanges',
			description: 'showChanges description'
		} );
		var showChangesStep = showChangesStepBuilder.step;
		var singlePageTourBuilder = new gt.TourBuilder( {
			name: 'singlePage',
			isSinglePage: true
		} );
		var singlePageStepBuilder = singlePageTourBuilder.step( {
			name: 'beginning',
			description: 'beginning description'
		} );
		var singlePageStep = singlePageStepBuilder.step;
		var updateUserStateSpy = this.spy( gt, 'updateUserStateForTour' );
		var unregisterSpy = this.spy( gt.Step.prototype, 'unregisterMwHooks' );

		// Use an empty jQuery context for elem since we're not testing the logging
		// event registration currently.
		firstStep.handleOnShow( { id: firstStep.specification.id, elem: $() } );
		assert.strictEqual(
			unregisterSpy.callCount,
			0,
			'unregisterMwHooks is not called when the first step is shown'
		);
		assert.deepEqual(
			updateUserStateSpy.callCount,
			1,
			'For a regular (isSinglePage false) tour, updateUserStateForTour is called'
		);
		assert.strictEqual(
			validTour.currentStep,
			firstStep,
			'currentStep is set after handleShow'
		);

		unregisterSpy.reset();
		showChangesStep.handleOnShow( { id: showChangesStep.specification.id, elem: $() } );
		assert.strictEqual(
			unregisterSpy.thisValues[ 0 ],
			firstStep,
			'mw.hook listeners for prior current step are unregistered'
		);

		updateUserStateSpy.reset();
		singlePageStep.handleOnShow( { id: singlePageStep.specification.id, elem: $() } );
		assert.strictEqual(
			updateUserStateSpy.callCount,
			0,
			'For an isSinglePage true tour, updateUserStateForTour is never called'
		);
	} );

	QUnit.test( 'TourBuilder.constructor', function ( assert ) {
		var CHECK_YOUR_SYNTAX = /Check your syntax. There must be exactly one argument, 'tourSpec', which must be an object/;

		assert.throws(
			function () {
				// eslint-disable-next-line no-unused-vars
				var tour = new gt.TourBuilder();
			},
			compareTypeAndMessage( gt.TourDefinitionError, CHECK_YOUR_SYNTAX ),
			'Throws if no tour specification is passed'
		);

		assert.throws(
			function () {
				// eslint-disable-next-line no-unused-vars
				var tour = new gt.TourBuilder( 'test' );
			},
			compareTypeAndMessage( gt.TourDefinitionError, CHECK_YOUR_SYNTAX ),
			'Throws if the tour specification is not an object'
		);

		assert.throws(
			function () {
				// eslint-disable-next-line no-unused-vars
				var tour = new gt.TourBuilder( {
					tourName: 'test'
				} );
			},
			compareTypeAndMessage( gt.TourDefinitionError, /'tourSpec.name' must be a string, the tour name/ )
		);

		assert.strictEqual(
			validTourBuilder.constructor,
			gt.TourBuilder,
			'Valid TourBuilder constructed in setup is constructed normally'
		);
	} );

	QUnit.test( 'TourBuilder.step', function ( assert ) {
		validTourBuilder.step( {
			name: 'preview',
			description: 'preview description'
		} );

		validTourBuilder.step( {
			name: 'save',
			description: 'save description'
		} );

		assert.strictEqual(
			validTour.stepCount,
			3,
			'stepCount is correct after multiple calls'
		);

		assert.throws(
			function () {
				validTourBuilder.step( {
					name: 'save',
					description: 'save description'
				} );
			},
			compareTypeAndMessage( gt.TourDefinitionError, /The name "save" is already taken\. {2}Two steps in a tour can not have the same name/ ),
			'Step cname can not repeat'
		);
	} );

	QUnit.test( 'TourBuilder.firstStep', function ( assert ) {
		var previewStepSpec = $.extend( {}, VALID_BUILDER_STEP_SPEC, { name: 'preview' } );

		assert.throws(
			function () {
				validTourBuilder.firstStep( previewStepSpec );
			},
			compareTypeAndMessage( gt.TourDefinitionError, /You can only specify one first step/ ),
			'Verify that TourBuilder.first can call once per candidate'
		);
	} );

	QUnit.test( 'Tour.constructor', function ( assert ) {
		var tour = new gt.Tour( {
			name: 'addImage'
		} );

		assert.strictEqual(
			gt.internal.definedTours[ tour.name ],
			tour,
			'Tour is defined in internal list after constructor'
		);
	} );

	QUnit.test( 'Tour.getShouldFlipHorizontally', function ( assert ) {
		// Full coverage of all code paths
		var EXTENSION_NAME = 'extension';
		var ONWIKI_NAME = 'onwiki';

		var getStateStub = this.stub( mw.loader, 'getState' );
		getStateStub.withArgs( gt.internal.getTourModuleName( ONWIKI_NAME ) )
			.returns( null );

		getStateStub.withArgs( gt.internal.getTourModuleName( EXTENSION_NAME ) )
			.returns( 'loaded' );

		var extensionTour = new gt.Tour( {
			name: EXTENSION_NAME
		} );

		var onwikiTour = new gt.Tour( {
			name: ONWIKI_NAME
		} );

		// There are two different directionalities
		// * Site as a whole (sitedir- class)
		// * User interface (html[dir])
		//
		// On wiki tours use the site language as their tour direction.
		// Extension tours don't care about site language; tour direction is ltr
		// Should flip if interface direction is different from tour direction

		assert.strictEqual(
			extensionTour.getShouldFlipHorizontally( 'ltr', 'ltr' ),
			false,
			'No flip for extension tour when interface language and site language are both ltr'
		);
		assert.strictEqual(
			onwikiTour.getShouldFlipHorizontally( 'ltr', 'ltr' ),
			false,
			'No flip for onwiki tour when interface language and site language are both ltr'
		);

		assert.strictEqual(
			extensionTour.getShouldFlipHorizontally( 'rtl', 'ltr' ),
			true,
			'Flip for extension tour when interface language is rtl and site language is ltr'
		);
		assert.strictEqual(
			onwikiTour.getShouldFlipHorizontally( 'rtl', 'ltr' ),
			true,
			'Flip for onwiki tour when interface language is rtl and site language is ltr'
		);

		assert.strictEqual(
			extensionTour.getShouldFlipHorizontally( 'ltr', 'rtl' ),
			false,
			'No flip for extension tour when interface language is ltr and site language is rtl'
		);
		assert.strictEqual(
			onwikiTour.getShouldFlipHorizontally( 'ltr', 'rtl' ),
			true,
			'Flip for onwiki tour when interface language is ltr and site language is rtl'
		);

		assert.strictEqual(
			extensionTour.getShouldFlipHorizontally( 'rtl', 'rtl' ),
			true,
			'Flip for extension tour when interface language is rtl and site language is rtl'
		);
		assert.strictEqual(
			onwikiTour.getShouldFlipHorizontally( 'rtl', 'rtl' ),
			false,
			'No flip for onwiki tour when interface language and site language are both rtl'
		);
	} );

	QUnit.test( 'Tour.initialize', function ( assert ) {
		var stepInitializeSpy = this.spy( gt.Step.prototype, 'initialize' );

		var previewStepBuilder = validTourBuilder.step( {
			name: 'preview',
			description: 'preview description'
		} );

		var done = assert.async();

		validTour.initialize().done( function () {
			assert.assertTrue(
				stepInitializeSpy.calledOn( firstStep ),
				'Initializing tour first time initializes first step'
			);

			assert.assertTrue(
				stepInitializeSpy.calledOn( previewStepBuilder.step ),
				'Initializing tour first time initializes other steps'
			);

			stepInitializeSpy.reset();

			validTour.initialize().done( function () {
				assert.strictEqual(
					stepInitializeSpy.callCount,
					0,
					'Steps are not reinitialized if Tour.initialize is called again'
				);

				done();
			} );
		} );
	} );

	QUnit.test( 'Tour.getStep', function ( assert ) {
		assert.strictEqual(
			validTour.getStep( 'intro' ),
			firstStep,
			'getStep can find step by name'
		);

		assert.strictEqual(
			validTour.getStep( firstStep ),
			firstStep,
			'getStep can validate that a Step belongs to the tour and return it'
		);

		assert.throws(
			function () {
				validTour.getStep( 'bogus' );
			},
			compareTypeAndMessage( gt.IllegalArgumentError, /Step "bogus" not found in the "placeholder" tour/ ),
			'Throws if a step name is not found in the tour'
		);

		assert.throws(
			function () {
				validTour.getStep( otherTourStepBuilder.step );
			},
			compareTypeAndMessage( gt.IllegalArgumentError, /Step object must belong to this tour \("placeholder"\)/ ),
			'Throws if a step object does not belong to the tour'
		);
	} );

	QUnit.test( 'Tour.showStep', function ( assert ) {
		var checkTransitionSpy = this.spy( firstStep, 'checkTransition' );

		validTour.showStep( firstStep );

		var expectedTransitionEvent = new gt.TransitionEvent();
		expectedTransitionEvent.type = gt.TransitionEvent.BUILTIN;
		expectedTransitionEvent.subtype = gt.TransitionEvent.TRANSITION_BEFORE_SHOW;

		return validTour.initialize().then( function () {
			var actualTransitionEvent = checkTransitionSpy.lastCall.args[ 0 ];

			assert.deepEqual(
				actualTransitionEvent,
				expectedTransitionEvent,
				'Calls checkTransition with expected event'
			);
		} );
	} );

	QUnit.test( 'Tour.start', function ( assert ) {
		var tourBuilder = new gt.TourBuilder( {
			name: 'reference'
		} );
		assert.throws(
			function () {
				tourBuilder.tour.start();
			},
			compareTypeAndMessage( gt.TourDefinitionError, /The \.firstStep\(\) method must be called for all tours/ ),
			'Throws if firstStep was not called'
		);
	} );
}() );
