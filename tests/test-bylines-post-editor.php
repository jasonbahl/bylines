<?php
/**
 * Class Test_Bylines
 *
 * @package Bylines
 */

use Bylines\Objects\Byline;
use Bylines\Utils;

/**
 * Test functionality related to bylines post editor
 */
class Test_Bylines_Post_Editor extends WP_UnitTestCase {

	/**
	 * Saving bylines generically
	 */
	public function test_save_bylines() {
		$post_id = $this->factory->post->create();
		$b1 = Byline::create( array(
			'slug'  => 'b1',
			'display_name' => 'Byline 1',
		) );
		$b2 = Byline::create( array(
			'slug'  => 'b2',
			'display_name' => 'Byline 2',
		) );
		// Mock a POST request.
		$_POST = array(
			'bylines' => array(
				$b1->term_id,
				$b2->term_id,
			),
		);
		do_action( 'save_post', $post_id, get_post( $post_id ) );
		$bylines = get_bylines( $post_id );
		$this->assertCount( 2, $bylines );
		$this->assertEquals( array( 'b1', 'b2' ), wp_list_pluck( $bylines, 'slug' ) );
	}

	/**
	 * Saving bylines by creating a new user
	 */
	public function test_save_bylines_create_new_user() {
		$post_id = $this->factory->post->create();
		$b1 = Byline::create( array(
			'slug'  => 'b1',
			'display_name' => 'Byline 1',
		) );
		$user_id = $this->factory->user->create( array(
			'display_name'  => 'Foo Bar',
			'user_nicename' => 'foobar',
		) );
		$this->assertFalse( Byline::get_by_user_id( $user_id ) );
		// Mock a POST request.
		$_POST = array(
			'bylines' => array(
				'u' . $user_id,
				$b1->term_id,
			),
		);
		do_action( 'save_post', $post_id, get_post( $post_id ) );
		$bylines = get_bylines( $post_id );
		$this->assertCount( 2, $bylines );
		$this->assertEquals( array( 'foobar', 'b1' ), wp_list_pluck( $bylines, 'slug' ) );
		$byline = Byline::get_by_user_id( $user_id );
		$this->assertInstanceOf( 'Bylines\Objects\Byline', $byline );
		$this->assertEquals( 'Foo Bar', $byline->display_name );
	}

	/**
	 * Saving bylines by repurposing an existing user
	 */
	public function test_save_bylines_existing_user() {
		$post_id = $this->factory->post->create();
		$b1 = Byline::create( array(
			'slug'  => 'b1',
			'display_name' => 'Byline 1',
		) );
		$user_id = $this->factory->user->create( array(
			'display_name'  => 'Foo Bar',
			'user_nicename' => 'foobar',
		) );
		$byline = Byline::create_from_user( $user_id );
		$this->assertInstanceOf( 'Bylines\Objects\Byline', $byline );
		// Mock a POST request.
		$_POST = array(
			'bylines' => array(
				'u' . $user_id,
				$b1->term_id,
			),
		);
		do_action( 'save_post', $post_id, get_post( $post_id ) );
		$bylines = get_bylines( $post_id );
		$this->assertCount( 2, $bylines );
		$this->assertEquals( array( 'foobar', 'b1' ), wp_list_pluck( $bylines, 'slug' ) );
	}

}