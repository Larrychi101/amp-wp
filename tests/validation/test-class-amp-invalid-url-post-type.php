<?php
/**
 * Tests for AMP_Invalid_URL_Post_Type class.
 *
 * @package AMP
 */

/**
 * Tests for AMP_Invalid_URL_Post_Type class.
 *
 * @covers AMP_Invalid_URL_Post_Type
 */
class Test_AMP_Invalid_URL_Post_Type extends \WP_UnitTestCase {

	const TESTED_CLASS = 'AMP_Invalid_URL_Post_Type';

	/**
	 * After a test method runs, reset any state in WordPress the test method might have changed.
	 */
	public function tearDown() {
		global $current_screen;
		parent::tearDown();
		$current_screen = null; // WPCS: override ok.
	}

	/**
	 * Test register.
	 *
	 * @covers \AMP_Invalid_URL_Post_Type::register()
	 * @covers \AMP_Invalid_URL_Post_Type::add_admin_hooks()
	 */
	public function test_register() {
		$this->assertFalse( is_admin() );

		AMP_Invalid_URL_Post_Type::register();
		$amp_post_type = get_post_type_object( AMP_Invalid_URL_Post_Type::POST_TYPE_SLUG );

		$this->assertTrue( in_array( AMP_Invalid_URL_Post_Type::POST_TYPE_SLUG, get_post_types(), true ) );
		$this->assertEquals( array(), get_all_post_type_supports( AMP_Invalid_URL_Post_Type::POST_TYPE_SLUG ) );
		$this->assertEquals( AMP_Invalid_URL_Post_Type::POST_TYPE_SLUG, $amp_post_type->name );
		$this->assertEquals( 'Invalid AMP Pages (URLs)', $amp_post_type->label );
		$this->assertEquals( false, $amp_post_type->public );
		$this->assertTrue( $amp_post_type->show_ui );
		$this->assertEquals( AMP_Options_Manager::OPTION_NAME, $amp_post_type->show_in_menu );
		$this->assertTrue( $amp_post_type->show_in_admin_bar );
		$this->assertNotContains( AMP_Invalid_URL_Post_Type::REMAINING_ERRORS, wp_removable_query_args() );

		// Make sure that add_admin_hooks() gets called.
		set_current_screen( 'index.php' );
		AMP_Invalid_URL_Post_Type::register();
		$this->assertContains( AMP_Invalid_URL_Post_Type::REMAINING_ERRORS, wp_removable_query_args() );
	}

	/**
	 * Test add_admin_hooks.
	 *
	 * @covers \AMP_Invalid_URL_Post_Type::add_admin_hooks()
	 */
	public function test_add_admin_hooks() {
		AMP_Invalid_URL_Post_Type::add_admin_hooks();

		$this->assertEquals( 10, has_filter( 'dashboard_glance_items', array( self::TESTED_CLASS, 'filter_dashboard_glance_items' ) ) );
		$this->assertEquals( 10, has_action( 'rightnow_end', array( self::TESTED_CLASS, 'print_dashboard_glance_styles' ) ) );
		$this->assertEquals( 10, has_action( 'add_meta_boxes', array( self::TESTED_CLASS, 'add_meta_boxes' ) ) );
		$this->assertEquals( 10, has_action( 'edit_form_top', array( self::TESTED_CLASS, 'print_url_as_title' ) ) );
		$this->assertEquals( 10, has_filter( 'the_title', array( self::TESTED_CLASS, 'filter_the_title_in_post_list_table' ) ) );

		$this->assertEquals( 10, has_filter( 'views_edit-' . AMP_Invalid_URL_Post_Type::POST_TYPE_SLUG, array( self::TESTED_CLASS, 'filter_views_edit' ) ) );
		$this->assertEquals( 10, has_filter( 'manage_' . AMP_Invalid_URL_Post_Type::POST_TYPE_SLUG . '_posts_columns', array( self::TESTED_CLASS, 'add_post_columns' ) ) );
		$this->assertEquals( 10, has_action( 'manage_posts_custom_column', array( self::TESTED_CLASS, 'output_custom_column' ) ) );
		$this->assertEquals( 10, has_filter( 'post_row_actions', array( self::TESTED_CLASS, 'filter_row_actions' ) ) );
		$this->assertEquals( 10, has_filter( 'bulk_actions-edit-' . AMP_Invalid_URL_Post_Type::POST_TYPE_SLUG, array( self::TESTED_CLASS, 'add_bulk_action' ) ) );
		$this->assertEquals( 10, has_filter( 'handle_bulk_actions-edit-' . AMP_Invalid_URL_Post_Type::POST_TYPE_SLUG, array( self::TESTED_CLASS, 'handle_bulk_action' ) ) );
		$this->assertEquals( 10, has_action( 'admin_notices', array( self::TESTED_CLASS, 'print_admin_notice' ) ) );
		$this->assertEquals( 10, has_action( 'admin_action_' . AMP_Invalid_URL_Post_Type::VALIDATE_ACTION, array( self::TESTED_CLASS, 'handle_validate_request' ) ) );
		$this->assertEquals( 10, has_action( 'post_action_' . AMP_Invalid_URL_Post_Type::UPDATE_POST_TERM_STATUS_ACTION, array( self::TESTED_CLASS, 'handle_validation_error_status_update' ) ) );
		$this->assertEquals( 10, has_action( 'admin_menu', array( self::TESTED_CLASS, 'add_admin_menu_new_invalid_url_count' ) ) );

		$post = $this->factory()->post->create_and_get( array( 'post_type' => AMP_Invalid_URL_Post_Type::POST_TYPE_SLUG ) );
		$this->assertEquals( '', apply_filters( 'post_date_column_status', 'publish', $post ) );
		$this->assertEquals( 'publish', apply_filters( 'post_date_column_status', 'publish', $this->factory()->post->create_and_get() ) );

		$this->assertContains( 'amp_actioned', wp_removable_query_args() );
		$this->assertContains( 'amp_taxonomy_terms_updated', wp_removable_query_args() );
		$this->assertContains( AMP_Invalid_URL_Post_Type::REMAINING_ERRORS, wp_removable_query_args() );
		$this->assertContains( 'amp_urls_tested', wp_removable_query_args() );
		$this->assertContains( 'amp_validate_error', wp_removable_query_args() );
	}

	/**
	 * Test add_admin_menu_new_invalid_url_count.
	 *
	 * @covers \AMP_Invalid_URL_Post_Type::add_admin_menu_new_invalid_url_count()
	 */
	public function test_add_admin_menu_new_invalid_url_count() {
		global $submenu;
		AMP_Validation_Manager::init(); // Register the post type and taxonomy.

		unset( $submenu[ AMP_Options_Manager::OPTION_NAME ] );
		AMP_Invalid_URL_Post_Type::add_admin_menu_new_invalid_url_count();

		$submenu[ AMP_Options_Manager::OPTION_NAME ] = array( // WPCS: override ok.
			0 => array(
				0 => 'General',
				1 => 'manage_options',
				2 => 'amp-options',
				3 => 'AMP Settings',
			),
			1 => array(
				0 => 'Analytics',
				1 => 'manage_options',
				2 => 'amp-analytics-options',
				3 => 'AMP Analytics Options',
			),
			2 => array(
				0 => 'Invalid Pages',
				1 => 'edit_posts',
				2 => 'edit.php?post_type=amp_invalid_url',
				3 => 'Invalid AMP Pages (URLs)',
			),
		);

		$invalid_url_post_id = AMP_Invalid_URL_Post_Type::store_validation_errors(
			array(
				array(
					'code' => 'hello',
				),
			),
			get_permalink( $this->factory()->post->create() )
		);
		$this->assertNotInstanceOf( 'WP_Error', $invalid_url_post_id );

		AMP_Invalid_URL_Post_Type::add_admin_menu_new_invalid_url_count();

		$this->assertContains( '<span class="awaiting-mod"><span class="pending-count">1</span></span>', $submenu[ AMP_Options_Manager::OPTION_NAME ][2][0] );
	}

	/**
	 * Test get_invalid_url_validation_errors and display_invalid_url_validation_error_counts_summary.
	 *
	 * @covers \AMP_Invalid_URL_Post_Type::get_invalid_url_validation_errors()
	 * @covers \AMP_Invalid_URL_Post_Type::display_invalid_url_validation_error_counts_summary()
	 * @covers \AMP_Invalid_URL_Post_Type::store_validation_errors()
	 */
	public function test_get_invalid_url_validation_errors() {
		add_theme_support( 'amp', array( 'paired' => true ) );
		AMP_Validation_Manager::init();
		$post = $this->factory()->post->create();
		$this->assertEmpty( AMP_Invalid_URL_Post_Type::get_invalid_url_validation_errors( get_permalink( $post ) ) );

		add_filter( 'amp_validation_error_sanitized', function( $sanitized, $error ) {
			if ( 'accepted' === $error['code'] ) {
				$sanitized = true;
			} elseif ( 'rejected' === $error['code'] ) {
				$sanitized = false;
			}
			return $sanitized;
		}, 10, 2 );

		$invalid_url_post_id = AMP_Invalid_URL_Post_Type::store_validation_errors(
			array(
				array( 'code' => 'accepted' ),
				array( 'code' => 'rejected' ),
				array( 'code' => 'new' ),
			),
			get_permalink( $post )
		);
		$this->assertNotInstanceOf( 'WP_Error', $invalid_url_post_id );

		$errors = AMP_Invalid_URL_Post_Type::get_invalid_url_validation_errors( get_permalink( $post ) );
		$this->assertCount( 3, $errors );

		$error = array_shift( $errors );
		$this->assertEquals( 'accepted', $error['data']['code'] );
		$this->assertEquals( AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_ACCEPTED_STATUS, $error['term_status'] );
		$error = array_shift( $errors );
		$this->assertEquals( 'rejected', $error['data']['code'] );
		$this->assertEquals( AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_REJECTED_STATUS, $error['term_status'] );
		$error = array_shift( $errors );
		$this->assertEquals( 'new', $error['data']['code'] );
		$this->assertEquals( AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_NEW_STATUS, $error['term_status'] );

		$errors = AMP_Invalid_URL_Post_Type::get_invalid_url_validation_errors( get_permalink( $post ), array( 'ignore_accepted' => true ) );
		$this->assertCount( 2, $errors );
		$error = array_shift( $errors );
		$this->assertEquals( 'rejected', $error['data']['code'] );
		$this->assertEquals( AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_REJECTED_STATUS, $error['term_status'] );
		$error = array_shift( $errors );
		$this->assertEquals( 'new', $error['data']['code'] );
		$this->assertEquals( AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_NEW_STATUS, $error['term_status'] );

		ob_start();
		AMP_Invalid_URL_Post_Type::display_invalid_url_validation_error_counts_summary( $invalid_url_post_id );
		$summary = ob_get_clean();
		$this->assertContains( 'New: 1', $summary );
		$this->assertContains( 'Accepted: 1', $summary );
		$this->assertContains( 'Rejected: 1', $summary );
	}

	/**
	 * Test for get_invalid_url_post().
	 *
	 * @covers \AMP_Invalid_URL_Post_Type::get_invalid_url_post()
	 */
	public function test_get_invalid_url_post() {
		add_theme_support( 'amp', array( 'paired' => true ) );
		AMP_Validation_Manager::init();
		$post = $this->factory()->post->create_and_get();
		$this->assertEquals( null, AMP_Invalid_URL_Post_Type::get_invalid_url_post( get_permalink( $post ) ) );

		$invalid_post_id = AMP_Invalid_URL_Post_Type::store_validation_errors(
			array(
				array( 'code' => 'foo' ),
			),
			get_permalink( $post )
		);
		$this->assertNotInstanceOf( 'WP_Error', $invalid_post_id );

		$this->assertEquals(
			$invalid_post_id,
			AMP_Invalid_URL_Post_Type::get_invalid_url_post( get_permalink( $post ) )->ID
		);
	}

	/**
	 * Test get_url_from_post.
	 *
	 * @covers \AMP_Invalid_URL_Post_Type::get_url_from_post()
	 */
	public function test_get_url_from_post() {
		add_theme_support( 'amp', array( 'paired' => true ) );
		AMP_Validation_Manager::init();
		$post = $this->factory()->post->create_and_get();

		$this->assertNull( AMP_Invalid_URL_Post_Type::get_url_from_post( 0 ) );
		$this->assertNull( AMP_Invalid_URL_Post_Type::get_url_from_post( $post ) );

		$invalid_post_id = AMP_Invalid_URL_Post_Type::store_validation_errors(
			array(
				array( 'code' => 'foo' ),
			),
			get_permalink( $post )
		);
		$this->assertNotInstanceOf( 'WP_Error', $invalid_post_id );

		$this->assertEquals(
			add_query_arg( amp_get_slug(), '', get_permalink( $post ) ),
			AMP_Invalid_URL_Post_Type::get_url_from_post( $invalid_post_id )
		);

		add_theme_support( 'amp', array( 'paired' => false ) );
		$this->assertEquals(
			get_permalink( $post ),
			AMP_Invalid_URL_Post_Type::get_url_from_post( $invalid_post_id )
		);
	}

	/**
	 * Test for store_validation_errors()
	 *
	 * @covers \AMP_Invalid_URL_Post_Type::store_validation_errors()
	 */
	public function test_store_validation_errors() {
		add_theme_support( 'amp', array( 'paired' => true ) );
		AMP_Validation_Manager::init();
		$post = $this->factory()->post->create_and_get();

		add_filter( 'amp_validation_error_sanitized', function( $sanitized, $error ) {
			if ( 'accepted' === $error['code'] ) {
				$sanitized = true;
			} elseif ( 'rejected' === $error['code'] ) {
				$sanitized = false;
			}
			return $sanitized;
		}, 10, 2 );

		$errors = array(
			array(
				'code'    => 'accepted',
				'sources' => array(
					array(
						'type' => 'plugin',
						'name' => 'amp',
						'evil' => '<script>\o/</script>', // Test slash preservation and kses suspension.
					),
				),
			),
			array(
				'code'    => 'rejected',
				'evil'    => '<script>\o/</script>', // Test slash preservation and kses suspension.
				'sources' => array(
					array(
						'type' => 'theme',
						'name' => 'twentyseventeen',
					),
				),
			),
			array(
				'code'    => 'new',
				'sources' => array(
					array(
						'type' => 'core',
						'name' => 'wp-includes',
					),
				),
			),
		);

		$invalid_url_post_id = AMP_Invalid_URL_Post_Type::store_validation_errors(
			$errors,
			get_permalink( $post )
		);
		$this->assertNotInstanceOf( 'WP_Error', $invalid_url_post_id );

		// Test resurrection from trash.
		wp_trash_post( $invalid_url_post_id );
		$this->assertEquals(
			$invalid_url_post_id,
			AMP_Invalid_URL_Post_Type::store_validation_errors(
				$errors,
				get_permalink( $post )
			)
		);
		$this->assertEquals( 'publish', get_post_status( $invalid_url_post_id ) );

		// Test passing specific post to override the URL.
		$this->assertEquals(
			$invalid_url_post_id,
			AMP_Invalid_URL_Post_Type::store_validation_errors(
				$errors,
				home_url( '/something/else/' ),
				$invalid_url_post_id
			)
		);

		$this->assertEquals(
			home_url( '/something/else/' ),
			get_post( $invalid_url_post_id )->post_title
		);

		$stored_errors = AMP_Invalid_URL_Post_Type::get_invalid_url_validation_errors( $invalid_url_post_id );
		$this->assertEquals(
			$errors,
			array_map(
				function( $stored_error ) {
					return $stored_error['data'];
				},
				$stored_errors
			)
		);

		$error_groups = array(
			AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_ACCEPTED_STATUS,
			AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_REJECTED_STATUS,
			AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_NEW_STATUS,
		);

		foreach ( $errors as $i => $error ) {
			$stored_error = $stored_errors[ $i ];

			$this->assertEquals( $error, $stored_error['data'] );

			$sourceless_error = $error;
			unset( $sourceless_error['sources'] );

			/**
			 * Term.
			 *
			 * @var WP_Term $term
			 */
			$term = $stored_error['term'];
			$this->assertEquals( $sourceless_error, json_decode( $term->description, true ) );

			$this->assertNotEmpty( get_term_meta( $term->term_id, 'created_date_gmt', true ) );
			$this->assertEquals( $error_groups[ $i ], $stored_error['term_status'] );
			$this->assertEquals( $error_groups[ $i ], $term->term_group );
		}
	}

	/**
	 * Test filter_views_edit.
	 *
	 * @covers \AMP_Invalid_URL_Post_Type::filter_views_edit()
	 */
	public function test_filter_views_edit() {
		$_SERVER['REQUEST_URI'] = '/wp-admin/edit.php?post_type=amp_invalid_url&amp_validation_error_status=0';

		add_theme_support( 'amp', array( 'paired' => true ) );
		AMP_Validation_Manager::init();
		AMP_Validation_Error_Taxonomy::add_admin_hooks();
		$post = $this->factory()->post->create();
		$this->assertEmpty( AMP_Invalid_URL_Post_Type::get_invalid_url_validation_errors( get_permalink( $post ) ) );

		add_filter( 'amp_validation_error_sanitized', function( $sanitized, $error ) {
			if ( 'accepted' === $error['code'] ) {
				$sanitized = true;
			} elseif ( 'rejected' === $error['code'] ) {
				$sanitized = false;
			}
			return $sanitized;
		}, 10, 2 );

		$errors = array(
			array( 'code' => 'accepted' ),
			array( 'code' => 'rejected' ),
			array( 'code' => 'new' ),
		);

		$invalid_url_post_id = AMP_Invalid_URL_Post_Type::store_validation_errors(
			$errors,
			get_permalink( $post )
		);
		$this->assertNotInstanceOf( 'WP_Error', $invalid_url_post_id );

		$views = AMP_Invalid_URL_Post_Type::filter_views_edit( array(
			'publish' => 'Published',
		) );

		$this->assertArrayNotHasKey( 'publish', $views );
		$this->assertContains( '(1)', $views['new'] );
		$this->assertContains( '(1)', $views['rejected'] );
		$this->assertContains( '(1)', $views['accepted'] );

		$terms = get_terms( array( 'taxonomy' => AMP_Validation_Error_Taxonomy::TAXONOMY_SLUG ) );
		foreach ( $terms as $term ) {
			wp_update_term( $term->term_id, AMP_Validation_Error_Taxonomy::TAXONOMY_SLUG, array(
				'term_group' => AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_ACCEPTED_STATUS,
			) );
		}

		$views = AMP_Invalid_URL_Post_Type::filter_views_edit( array(
			'publish' => 'Published',
		) );
		$this->assertArrayNotHasKey( 'publish', $views );
		$this->assertContains( '(0)', $views['new'] );
		$this->assertContains( '(0)', $views['rejected'] );
		$this->assertContains( '(1)', $views['accepted'] );
	}

	/**
	 * Test for add_post_columns()
	 *
	 * @covers AMP_Invalid_URL_Post_Type::add_post_columns()
	 */
	public function test_add_post_columns() {
		$initial_columns = array(
			'cb' => '<input type="checkbox">',
		);
		$this->assertEquals(
			array_merge(
				$initial_columns,
				array(
					AMP_Validation_Error_Taxonomy::REMOVED_ELEMENTS => 'Removed Elements',
					AMP_Validation_Error_Taxonomy::REMOVED_ATTRIBUTES => 'Removed Attributes',
					AMP_Validation_Error_Taxonomy::SOURCES_INVALID_OUTPUT => 'Incompatible Sources',
					'error_status' => 'Error Status',
				)
			),
			AMP_Invalid_URL_Post_Type::add_post_columns( $initial_columns )
		);
	}

	/**
	 * Gets the test data for test_output_custom_column().
	 *
	 * @return array $columns
	 */
	public function get_custom_columns() {
		$source = array(
			'type' => 'plugin',
			'name' => 'amp',
		);
		$errors = array(
			array(
				'code'      => AMP_Validation_Error_Taxonomy::INVALID_ELEMENT_CODE,
				'node_name' => 'script',
				'sources'   => array( $source ),
			),
			array(
				'code'      => AMP_Validation_Error_Taxonomy::INVALID_ATTRIBUTE_CODE,
				'node_name' => 'onclick',
				'sources'   => array( $source ),
			),
		);

		return array(
			'invalid_element'       => array(
				AMP_Validation_Error_Taxonomy::REMOVED_ELEMENTS,
				'script',
				$errors,
			),
			'removed_attributes'    => array(
				AMP_Validation_Error_Taxonomy::REMOVED_ATTRIBUTES,
				'onclick',
				$errors,
			),
			'sources_invalid_input' => array(
				AMP_Validation_Error_Taxonomy::SOURCES_INVALID_OUTPUT,
				'amp',
				$errors,
			),
		);
	}

	/**
	 * Test for output_custom_column()
	 *
	 * @dataProvider get_custom_columns
	 * @covers       AMP_Invalid_URL_Post_Type::output_custom_column()
	 *
	 * @param string $column_name    The name of the column.
	 * @param string $expected_value The value that is expected to be present in the column markup.
	 * @param array  $errors         Errors.
	 */
	public function test_output_custom_column( $column_name, $expected_value, $errors ) {
		AMP_Validation_Manager::init();
		$invalid_url_post_id = AMP_Invalid_URL_Post_Type::store_validation_errors( $errors, home_url( '/' ) );

		ob_start();
		AMP_Invalid_URL_Post_Type::output_custom_column( $column_name, $invalid_url_post_id );
		$this->assertContains( $expected_value, ob_get_clean() );
	}

	/**
	 * Test for filter_row_actions()
	 *
	 * @covers \AMP_Invalid_URL_Post_Type::filter_row_actions()
	 */
	public function test_filter_row_actions() {
		add_theme_support( 'amp' );
		AMP_Validation_Manager::init();

		$initial_actions = array(
			'trash' => '<a href="https://example.com">Trash</a>',
		);

		$invalid_post_id = AMP_Invalid_URL_Post_Type::store_validation_errors(
			array(
				array( 'code' => 'foo' ),
			),
			home_url( '/' )
		);

		$this->assertEquals( $initial_actions, AMP_Invalid_URL_Post_Type::filter_row_actions( $initial_actions, $this->factory()->post->create_and_get() ) );

		$actions = AMP_Invalid_URL_Post_Type::filter_row_actions( $initial_actions, get_post( $invalid_post_id ) );
		$this->assertArrayNotHasKey( 'inline hide-if-no-js', $actions );
		$this->assertArrayHasKey( 'view', $actions );
		$this->assertArrayHasKey( AMP_Invalid_URL_Post_Type::VALIDATE_ACTION, $actions );

		$this->assertEquals( $initial_actions['trash'], $actions['trash'] );
	}

	/**
	 * Test for add_bulk_action()
	 *
	 * @covers \AMP_Invalid_URL_Post_Type::add_bulk_action()
	 */
	public function test_add_bulk_action() {
		$initial_action = array(
			'edit' => 'Edit',
		);
		$actions        = AMP_Invalid_URL_Post_Type::add_bulk_action( $initial_action );
		$this->assertFalse( isset( $action['edit'] ) );
		$this->assertEquals( 'Recheck', $actions[ AMP_Invalid_URL_Post_Type::BULK_VALIDATE_ACTION ] );
	}

	/**
	 * Test for handle_bulk_action()
	 *
	 * @covers \AMP_Invalid_URL_Post_Type::handle_bulk_action()
	 */
	public function test_handle_bulk_action() {
		add_theme_support( 'amp', array( 'paired' => true ) );
		AMP_Validation_Manager::init();

		$invalid_post_id = AMP_Invalid_URL_Post_Type::store_validation_errors(
			array(
				array( 'code' => 'foo' ),
			),
			home_url( '/' )
		);

		$initial_redirect = admin_url( 'plugins.php' );
		$items            = array( $invalid_post_id );
		$urls_tested      = (string) count( $items );

		$_GET[ AMP_Invalid_URL_Post_Type::URLS_TESTED ] = $urls_tested;

		// The action isn't correct, so the callback should return the URL unchanged.
		$this->assertEquals( $initial_redirect, AMP_Invalid_URL_Post_Type::handle_bulk_action( $initial_redirect, 'trash', $items ) );

		$that   = $this;
		$filter = function() use ( $that ) {
			return array(
				'body' => sprintf(
					'<html amp><head></head><body></body><!--%s--></html>',
					'AMP_VALIDATION_RESULTS:' . wp_json_encode( array_map(
						function( $error ) {
							return array_merge(
								compact( 'error' ),
								array( 'sanitized' => false )
							);
						},
						$that->get_mock_errors()
					) )
				),
			);
		};
		add_filter( 'pre_http_request', $filter, 10, 3 );
		$this->assertEquals(
			add_query_arg(
				array(
					AMP_Invalid_URL_Post_Type::URLS_TESTED => $urls_tested,
					AMP_Invalid_URL_Post_Type::REMAINING_ERRORS => count( $items ),
				),
				$initial_redirect
			),
			AMP_Invalid_URL_Post_Type::handle_bulk_action( $initial_redirect, AMP_Invalid_URL_Post_Type::BULK_VALIDATE_ACTION, $items )
		);
		remove_filter( 'pre_http_request', $filter, 10 );

		// Test error scenario.
		add_filter( 'pre_http_request', function() {
			return array(
				'body' => '<html></html>',
			);
		} );
		$this->assertEquals(
			add_query_arg(
				array(
					AMP_Invalid_URL_Post_Type::URLS_TESTED => $urls_tested,
					'amp_validate_error'                   => array( 'response_comment_absent' ),
				),
				$initial_redirect
			),
			AMP_Invalid_URL_Post_Type::handle_bulk_action( $initial_redirect, AMP_Invalid_URL_Post_Type::BULK_VALIDATE_ACTION, $items )
		);
	}

	/**
	 * Test for print_admin_notice()
	 *
	 * @covers \AMP_Invalid_URL_Post_Type::print_admin_notice()
	 */
	public function test_print_admin_notice() {
		add_theme_support( 'amp' );
		AMP_Validation_Manager::init();

		ob_start();
		AMP_Invalid_URL_Post_Type::print_admin_notice();
		$this->assertEmpty( ob_get_clean() );

		$_GET['post_type'] = 'post';
		ob_start();
		AMP_Invalid_URL_Post_Type::print_admin_notice();
		$this->assertEmpty( ob_get_clean() );

		set_current_screen( 'edit.php' );
		get_current_screen()->post_type = AMP_Invalid_URL_Post_Type::POST_TYPE_SLUG;

		$_GET[ AMP_Invalid_URL_Post_Type::REMAINING_ERRORS ] = '1';
		$_GET[ AMP_Invalid_URL_Post_Type::URLS_TESTED ]      = '1';
		ob_start();
		AMP_Invalid_URL_Post_Type::print_admin_notice();
		$this->assertContains( 'The rechecked URL still has unaccepted validation errors', ob_get_clean() );

		$_GET[ AMP_Invalid_URL_Post_Type::URLS_TESTED ] = '2';
		ob_start();
		AMP_Invalid_URL_Post_Type::print_admin_notice();
		$this->assertContains( 'The rechecked URLs still have unaccepted validation errors', ob_get_clean() );

		$_GET[ AMP_Invalid_URL_Post_Type::REMAINING_ERRORS ] = '0';
		ob_start();
		AMP_Invalid_URL_Post_Type::print_admin_notice();
		$this->assertContains( 'The rechecked URLs are free of unaccepted validation errors', ob_get_clean() );

		$_GET[ AMP_Invalid_URL_Post_Type::URLS_TESTED ] = '1';
		ob_start();
		AMP_Invalid_URL_Post_Type::print_admin_notice();
		$this->assertContains( 'The rechecked URL is free of unaccepted validation errors', ob_get_clean() );

		$_GET['amp_validate_error'] = array( 'http_request_failed' );
		ob_start();
		AMP_Invalid_URL_Post_Type::print_admin_notice();
		$this->assertContains( 'Failed to fetch URL(s) to validate', ob_get_clean() );

		unset( $GLOBALS['current_screen'] );
	}

	/**
	 * Test for handle_validate_request()
	 *
	 * @covers \AMP_Invalid_URL_Post_Type::handle_validate_request()
	 */
	public function test_handle_validate_request() {
		$this->markTestSkipped( 'Needs rewrite for refactor' );

		$post_id              = $this->create_custom_post();
		$_REQUEST['_wpnonce'] = wp_create_nonce( AMP_Validation_Manager::NONCE_ACTION . $post_id );
		wp_set_current_user( $this->factory()->user->create( array(
			'role' => 'administrator',
		) ) );

		try {
			AMP_Validation_Manager::handle_inline_recheck( $post_id );
		} catch ( WPDieException $e ) {
			$exception = $e;
		}

		// This calls wp_redirect(), which throws an exception.
		$this->assertTrue( isset( $exception ) );
	}

	/**
	 * Test for recheck_post()
	 *
	 * @covers \AMP_Invalid_URL_Post_Type::recheck_post()
	 */
	public function test_recheck_post() {
		$this->markTestSkipped( 'Needs refactoring' );
	}

	/**
	 * Test for handle_validation_error_status_update()
	 *
	 * @covers \AMP_Invalid_URL_Post_Type::handle_validation_error_status_update()
	 */
	public function test_handle_validation_error_status_update() {
		$this->markTestSkipped( 'Needs refactoring' );
	}

	/**
	 * Test for add_meta_boxes()
	 *
	 * @covers \AMP_Invalid_URL_Post_Type::add_meta_boxes()
	 */
	public function test_add_meta_boxes() {
		$this->markTestSkipped( 'Needs refactoring' );

		global $wp_meta_boxes;
		AMP_Validation_Manager::add_meta_boxes();
		$side_meta_box = $wp_meta_boxes[ AMP_Validation_Manager::POST_TYPE_SLUG ]['side']['default'][ AMP_Validation_Manager::STATUS_META_BOX ];
		$this->assertEquals( AMP_Validation_Manager::STATUS_META_BOX, $side_meta_box['id'] );
		$this->assertEquals( 'Status', $side_meta_box['title'] );
		$this->assertEquals(
			array(
				self::TESTED_CLASS,
				'print_status_meta_box',
			),
			$side_meta_box['callback']
		);

		$full_meta_box = $wp_meta_boxes[ AMP_Validation_Manager::POST_TYPE_SLUG ]['normal']['default'][ AMP_Validation_Manager::VALIDATION_ERRORS_META_BOX ];
		$this->assertEquals( AMP_Validation_Manager::VALIDATION_ERRORS_META_BOX, $full_meta_box['id'] );
		$this->assertEquals( 'Validation Errors', $full_meta_box['title'] );
		$this->assertEquals(
			array(
				self::TESTED_CLASS,
				'print_validation_errors_meta_box',
			),
			$full_meta_box['callback']
		);

		global $wp_meta_boxes;
		AMP_Validation_Manager::remove_publish_meta_box();
		$contexts = $wp_meta_boxes[ AMP_Validation_Manager::POST_TYPE_SLUG ]['side'];
		foreach ( $contexts as $context ) {
			$this->assertFalse( $context['submitdiv'] );
		}
	}

	/**
	 * Test for print_status_meta_box()
	 *
	 * @covers \AMP_Invalid_URL_Post_Type::print_status_meta_box()
	 */
	public function test_print_status_meta_box() {
		$this->markTestSkipped( 'Needs rewrite for refactor' );

		$this->set_capability();
		$post_storing_error = get_post( $this->create_custom_post() );
		$url                = get_post_meta( $post_storing_error->ID, AMP_Validation_Manager::AMP_URL_META, true );
		$post_with_error    = AMP_Validation_Manager::get_invalid_url_post( $url );
		ob_start();
		AMP_Validation_Manager::print_status_meta_box( $post_storing_error );
		$output = ob_get_clean();

		$this->assertContains( date_i18n( 'M j, Y @ H:i', strtotime( $post_with_error->post_date ) ), $output );
		$this->assertContains( 'Published on:', $output );
		$this->assertContains( 'Move to Trash', $output );
		$this->assertContains( esc_url( get_delete_post_link( $post_storing_error->ID ) ), $output );
		$this->assertContains( 'misc-pub-section', $output );
		$this->assertContains(
			AMP_Validation_Manager::get_recheck_link(
				$post_with_error,
				add_query_arg(
					'post',
					$post_with_error->ID,
					admin_url( 'post.php' )
				)
			),
			$output
		);
	}

	/**
	 * Test for print_validation_errors_meta_box()
	 *
	 * @covers \AMP_Invalid_URL_Post_Type::print_validation_errors_meta_box()
	 */
	public function test_print_validation_errors_meta_box() {
		$this->markTestSkipped( 'Needs rewrite for refactor' );
		$this->set_capability();
		$post_storing_error     = get_post( $this->create_custom_post() );
		$first_url              = get_post_meta( $post_storing_error->ID, AMP_Validation_Manager::AMP_URL_META, true );
		$second_url_same_errors = get_permalink( $this->factory()->post->create() );
		AMP_Validation_Manager::store_validation_errors( $this->get_mock_errors(), $second_url_same_errors );
		ob_start();
		AMP_Validation_Manager::print_validation_errors_meta_box( $post_storing_error );
		$output = ob_get_clean();

		$this->assertContains( '<details', $output );
		$this->assertContains( $this->disallowed_tag_name, $output );
		$this->assertContains( $this->disallowed_attribute_name, $output );
		$this->assertContains( 'URLs', $output );
		$this->assertContains( $first_url, $output );
		$this->assertContains( $second_url_same_errors, $output );
		AMP_Validation_Manager::reset_validation_results();
	}

	/**
	 * Test for print_url_as_title()
	 *
	 * @covers \AMP_Invalid_URL_Post_Type::print_url_as_title()
	 */
	public function test_print_url_as_title() {
		$this->markTestSkipped( 'Needs refactoring' );
	}

	/**
	 * Test for filter_the_title_in_post_list_table()
	 *
	 * @covers \AMP_Invalid_URL_Post_Type::filter_the_title_in_post_list_table()
	 */
	public function test_filter_the_title_in_post_list_table() {
		global $current_screen;
		$post  = $this->factory()->post->create_and_get();
		$title = 'https://example.com/baz';
		set_current_screen( 'front' );

		// The first conditional isn't true yet, so $title should be unchanged.
		$this->assertEquals( $title, AMP_Invalid_URL_Post_Type::filter_the_title_in_post_list_table( $title, $post ) );

		/*
		 * The first conditional still isn't true yet, as the $post->post_type isn't correct.
		 * So this should again return $ttile unchanged.
		 */
		set_current_screen( 'edit.php' );
		$current_screen->post_type = AMP_Invalid_URL_Post_Type::POST_TYPE_SLUG;
		$this->assertEquals( $title, AMP_Invalid_URL_Post_Type::filter_the_title_in_post_list_table( $title, $post ) );

		// The conditional should be true, and this should return the filtered $title.
		$post_correct_post_type = $this->factory()->post->create_and_get( array(
			'post_type' => AMP_Invalid_URL_Post_Type::POST_TYPE_SLUG,
		) );
		$this->assertEquals( '/baz', AMP_Invalid_URL_Post_Type::filter_the_title_in_post_list_table( $title, $post_correct_post_type ) );
	}

	/**
	 * Test for get_recheck_url()
	 *
	 * @covers \AMP_Invalid_URL_Post_Type::get_recheck_url()
	 */
	public function test_get_recheck_url() {
		$this->markTestSkipped( 'Needs rewrite for refactor' );

		$this->set_capability();
		$post_id = $this->create_custom_post();
		$url     = get_edit_post_link( $post_id, 'raw' );
		$link    = AMP_Validation_Manager::get_recheck_url( get_post( $post_id ), $url );
		$this->assertContains( AMP_Validation_Manager::RECHECK_ACTION, $link );
		$this->assertContains( wp_create_nonce( AMP_Validation_Manager::NONCE_ACTION . $post_id ), $link );
		$this->assertContains( 'Recheck the URL for AMP validity', $link );
	}

	/**
	 * Test for filter_dashboard_glance_items()
	 *
	 * @covers \AMP_Invalid_URL_Post_Type::filter_dashboard_glance_items()
	 */
	public function test_filter_dashboard_glance_items() {

		// There are no validation errors, so this should return the argument unchanged.
		$this->assertEmpty( AMP_Invalid_URL_Post_Type::filter_dashboard_glance_items( array() ) );

		// Create validation errors, so that the method returns items.
		$post_id = $this->factory()->post->create();
		AMP_Invalid_URL_Post_Type::store_validation_errors(
			array(
				array( 'code' => 'accepted' ),
				array( 'code' => 'rejected' ),
				array( 'code' => 'new' ),
			),
			get_permalink( $post_id )
		);
		$items = AMP_Invalid_URL_Post_Type::filter_dashboard_glance_items( array() );
		$this->assertContains( '1 URL w/ new AMP errors', $items[0] );
		$this->assertContains( AMP_Invalid_URL_Post_Type::POST_TYPE_SLUG, $items[0] );
		$this->assertContains( AMP_Validation_Error_Taxonomy::VALIDATION_ERROR_STATUS_QUERY_VAR, $items[0] );
	}

	/**
	 * Gets mock errors for tests.
	 *
	 * @return array $errors[][] {
	 *     The data of the validation errors.
	 *
	 *     @type string    $code        Error code.
	 *     @type string    $node_name   Name of removed node.
	 *     @type string    $parent_name Name of parent node.
	 *     @type array[][] $sources     Source data, including plugins and themes.
	 * }
	 */
	public function get_mock_errors() {
		return array(
			array(
				'code'            => AMP_Validation_Error_Taxonomy::INVALID_ELEMENT_CODE,
				'node_name'       => 'script',
				'parent_name'     => 'div',
				'node_attributes' => array(),
				'sources'         => array(
					array(
						'type' => 'plugin',
						'name' => 'amp',
					),
				),
			),
			array(
				'code'               => AMP_Validation_Error_Taxonomy::INVALID_ATTRIBUTE_CODE,
				'node_name'          => 'onclick',
				'parent_name'        => 'div',
				'element_attributes' => array(
					'onclick' => '',
				),
				'sources'            => array(
					array(
						'type' => 'plugin',
						'name' => 'amp',
					),
				),
			),
		);
	}
}
