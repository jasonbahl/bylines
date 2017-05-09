<?php

namespace Bylines\Integrations\GraphQL;

use Bylines\Content_Model;
use Bylines\Integrations\GraphQL\Type\BylineType;
use Bylines\Objects\Byline;
use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\AppContext;
use WPGraphQL\Types;

/**
 * Class GraphQL
 *
 * This class organizes the functionality to connect Bylines to WPGraphQL
 *
 * @package Bylines\Integrations\GraphQL
 */
class GraphQL {

	/**
	 * Holds the instance of the BylineType
	 *
	 * @var BylineType
	 */
	private static $byline_type;

	/**
	 * This initializes the functionality connecting Bylines to WPGraphQL
	 */
	public static function init() {
		add_action( 'do_graphql_request', array(
			'Bylines\Integrations\GraphQL\GraphQL',
			'filter_post_object_fields'
		) );
		add_filter( 'graphql_default_query_args', array(
			'Bylines\Integrations\GraphQL\GraphQL',
			'filter_byline_post_object_query_args'
		), 10, 5 );
		add_filter( 'graphql_resolve_node', array(
			'Bylines\Integrations\GraphQL\GraphQL',
			'filter_resolve_node',
		), 10, 3 );
		add_filter( 'graphql_resolve_node_type', array(
			'Bylines\Integrations\GraphQL\GraphQL',
			'filter_resolve_node_type'
		), 10, 2 );
	}

	/**
	 * This filters each postObjectType that has both GraphQL support and Bylines support, adding the byline fields
	 *
	 * @return void
	 */
	public static function filter_post_object_fields() {

		$byline_post_types  = Content_Model::get_byline_supported_post_types();
		$graphql_post_types = \WPGraphQL::get_allowed_post_types();

		foreach ( $byline_post_types as $post_type ) {
			if ( in_array( $post_type, $graphql_post_types, true ) ) {
				add_filter( "graphql_{$post_type}_fields", [
					'Bylines\Integrations\GraphQL\GraphQL',
					'post_object_fields'
				], 10, 1 );
			}
		}

	}

	/**
	 * This adds the byline field to the existing $fields definition
	 *
	 * @param $fields
	 *
	 * @return mixed
	 */
	public function post_object_fields( $fields ) {

		$fields['bylines'] = [
			'type'        => Types::list_of( self::byline_type() ),
			'description' => __( 'The bylines for the object' ),
			'resolve'     => function( \WP_Post $post, array $args, AppContext $context, ResolveInfo $info ) {
				$bylines = get_bylines( $post );

				return ! empty( $bylines ) ? $bylines : null;
			}
		];

		return $fields;
	}

	/**
	 * This returns the definition for the BylineType, but ensures to only instantiate once
	 *
	 * @return BylineType
	 */
	public static function byline_type() {
		return self::$byline_type ? : ( self::$byline_type = new BylineType() );
	}

	/**
	 * This filters the post object query args so that if the source of the query is a byline, the query should
	 * be performed with a tax_query for the source byline.
	 *
	 * @param array       $query_args The query_args that will be returned
	 * @param array       $args       Query "where" args
	 * @param mixed       $source     The query results for a query calling this
	 * @param AppContext  $context    The AppContext object
	 * @param ResolveInfo $info       The ResolveInfo object
	 *
	 * @return mixed
	 */
	public static function filter_byline_post_object_query_args( $query_args, $source, $args, $context, $info ) {

		/**
		 * If the source of the query is a Byline, add tax_query args
		 */
		if ( true === is_object( $source ) ) {
			switch ( true ) {
				case $source instanceof Byline:
					$query_args['tax_query'] = [
						[
							'taxonomy' => 'byline',
							'terms'    => [ $source->term_id ],
							'field'    => 'term_id',
						],
					];
					break;
				default:
					break;
			}
		}

		/**
		 * Return the $query_args
		 */
		return $query_args;
	}

	/**
	 * This filters the node resolver to properly resolve with the Byline object if the $type is "byline"
	 *
	 * @param $node
	 * @param $id
	 * @param $type
	 *
	 * @return Byline|false
	 */
	public function filter_resolve_node( $node, $id, $type ) {
		if ( ! empty( $type ) && 'byline' === $type ) {
			return Byline::get_by_term_id( $id );
		}
		return $node;
	}

	/**
	 * This filters the node_type to ensure that the Byline "type" is returned if the object being returned is
	 * a Byline object
	 *
	 * @param $type
	 * @param $node
	 *
	 * @return BylineType
	 */
	public function filter_resolve_node_type( $type, $node ) {
		if ( is_object( $node ) && $node instanceof Byline ) {
			return self::byline_type();
		}
		return $type;
	}

}