<?php
namespace Bylines\Integrations\GraphQL\Type;

use Bylines\Content_Model;
use Bylines\Objects\Byline;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQLRelay\Relay;
use WPGraphQL\AppContext;
use WPGraphQL\Type\PostObject\Connection\PostObjectConnectionDefinition;
use WPGraphQL\Type\WPObjectType;
use WPGraphQL\Types;

/**
 * Class BylineType
 *
 * This defines the BylineType for use in the WPGraphQL schema.
 *
 * @package Bylines\Integrations\GraphQL\Type
 */
class BylineType extends WPObjectType {

	/**
	 * This holds the $fields definition for the BylineType
	 *
	 * @var array
	 */
	private static $fields;

	/**
	 * This holds the name of the type
	 *
	 * @var string
	 */
	private static $type_name;

	/**
	 * BylineType constructor.
	 *
	 * @param array $config
	 */
	public function __construct( array $config = [] ) {

		self::$type_name = 'byline';

		$config = [
			'name' => self::$type_name,
			'description' => __( 'The Byline object type', 'bylines' ),
			'fields' => self::fields(),
			'interfaces' => [ self::node_interface() ],
		];

		parent::__construct( $config );
	}

	/**
	 * This creates the $fields configuration for the BylineType
	 *
	 * @return \Closure|null
	 */
	public function fields() {

		if ( null === self::$fields ) :

			/**
			 * Get the supported post_types for GraphQL and Bylines
			 */
			$byline_post_types = Content_Model::get_byline_supported_post_types();
			$graphql_post_types = \WPGraphQL::get_allowed_post_types();

			self::$fields = function() use ( $byline_post_types, $graphql_post_types ) {

				$fields = [
					'id'                => [
						'type'    => Types::non_null( Types::id() ),
						'resolve' => function( Byline $byline, $args, AppContext $context, ResolveInfo $info ) {
							return ! empty( $byline->term_id ) ? Relay::toGlobalId( 'byline', $byline->term_id ) : null;
						},
					],
					'displayName' => [
						'type' => Types::string(),
						'description' => __( 'The display name of the byline', 'bylines' ),
						'resolve' => function( Byline $byline, $args, AppContext $context, ResolveInfo $info ) {
							$name = $byline->display_name;
							return ! empty( $name ) ? esc_html( $name ) : null;
						},
					],
					'firstName' => [
						'type' => Types::string(),
						'description' => __( 'The first name of the byline', 'bylines' ),
						'resolve' => function( Byline $byline, $args, AppContext $context, ResolveInfo $info ) {
							$first_name = $byline->first_name;
							return ! empty( $first_name ) ? esc_html( $first_name ) : null;
						},
					],
					'lastName' => [
						'type' => Types::string(),
						'description' => __( 'The last name of the byline', 'bylines' ),
						'resolve' => function( Byline $byline, $args, AppContext $context, ResolveInfo $info ) {
							$last_name = $byline->last_name;
							return ! empty( $last_name ) ? esc_html( $last_name ) : null;
						},
					],
					'bio' => [
						'type' => Types::string(),
						'description' => __( 'The biographical information for the byline', 'bylines' ),
						'resolve' => function( Byline $byline, $args, AppContext $context, ResolveInfo $info ) {
							$description = $byline->description;
							return ! empty( $description ) ? esc_html( $description ) : null;
						},
					],
					'email' => [
						'type' => Types::string(),
						'description' => __( 'The email associated with the byline', 'bylines' ),
						'resolve' => function( Byline $byline, $args, AppContext $context, ResolveInfo $info ) {
							$email = $byline->user_email;
							return ! empty( $email ) ? esc_html( $email ) : null;
						},
					],
					'url' => [
						'type' => Types::string(),
						'description' => __( 'The url (web address) associated with the byline', 'bylines' ),
						'resolve' => function( Byline $byline, $args, AppContext $context, ResolveInfo $info ) {
							$url = $byline->user_url;
							return ! empty( $url ) ? esc_html( $url ) : null;
						},
					],
				];

				/**
				 * Add a postObjectConnection field to the byline for each post_type that supports bylines
				 */
				foreach ( $byline_post_types as $post_type ) {
					if ( in_array( $post_type, $graphql_post_types, true ) ) :
						$post_type_object = get_post_type_object( $post_type );
						$fields[ $post_type_object->graphql_plural_name ] = PostObjectConnectionDefinition::connection( $post_type_object );
					endif;
				}

				/**
				 * Prepare the fields for use by WPGraphQL
				 */
				return self::prepare_fields( $fields, self::$type_name );

			};

		endif;

		/**
		 * Return the fields
		 */
		return ! empty( self::$fields ) ? self::$fields : null;

	}

}
