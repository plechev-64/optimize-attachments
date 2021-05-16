<?php

class OptAttachments {

	public $attachments = [];

	function __construct( $attachments ) {
		$this->attachments = $attachments;
	}

	function is_has( $id ) {
		return isset( $this->attachments[ $id ] );
	}

	function attachment( $id ) {
		return $this->attachments[ $id ];
	}

	function get_thumbnail_image( $post_id, $size, $atts = [] ) {

		if ( ! $this->is_has( $post_id ) ) {
			return false;
		}

		if ( $image = $this->attachment( $post_id )->get_image( $size, $atts ) ) {
			return $image;
		} else {
			return get_the_post_thumbnail( $post_id, $size, $atts );
		}
	}

	static function setup_post_thumbnails_by_object( $arrayObjects, $propName, $meta_key = '_thumbnail_id', $get_attached_file = false ) {

		$post_ids = [];
		foreach ( $arrayObjects as $object ) {
			$post_ids[] = $object->$propName;
		}

		return self::setup_post_thumbnails( $post_ids );

	}

	static function setup_post_thumbnails( $post_ids, $meta_key = '_thumbnail_id', $get_attached_file = false ) {

		$attachments = [];
		if ( $thumbnails = self::get_metadata_thumbnails( $post_ids, $meta_key, $get_attached_file ) ) {
			foreach ( $thumbnails as $thumbnail ) {
				$attachments[ $thumbnail->post_id ] = new OptAttachment( $thumbnail->attach_id, maybe_unserialize( $thumbnail->metadata ), !empty($thumbnail->attached_file) );
			}
		}

		return new OptAttachments( $attachments );

	}

	static function setup_attachments( $attach_ids ) {

		$attachments = [];
		if ( $thumbnails = self::get_metadata_attachments( $attach_ids ) ) {
			foreach ( $thumbnails as $thumbnail ) {
				$attachments[ $thumbnail->attach_id ] = new OptAttachment( $thumbnail->attach_id, maybe_unserialize( $thumbnail->metadata ) );
			}
		}

		return new OptAttachments( $attachments );

	}

	static function get_query_attachments_request( $attach_ids ) {

		return DBQuery::tbl( new PostMetaQuery() )->select( [
			'attach_id' => 'post_id',
			'metadata'  => 'meta_value'
		] )->where( [
			'meta_key'    => '_wp_attachment_metadata',
			'post_id__in' => array_diff( array_unique( $attach_ids ), [ '' ] ),
		] )->limit( - 1 );

	}

	static function get_metadata_attachments( $attach_ids ) {
		return self::get_query_attachments_request( $attach_ids )->get_results();
	}

	static function get_query_thumbnails_request( $post_ids, $meta_key = '_thumbnail_id', $get_attached_file = false ) {

		$query = DBQuery::tbl( new PostMetaQuery() )->select( [
			'post_id',
			'attach_id' => 'meta_value'
		] )->where( [
			'meta_key'    => $meta_key,
			'post_id__in' => $post_ids,
		] )->join(
			[ 'meta_value', 'post_id' ],
			DBQuery::tbl( new PostMetaQuery( 'metadata' ) )->select( [ 'metadata' => 'meta_value' ] )->where( [
				'meta_key' => '_wp_attachment_metadata'
			] )
		)->limit( - 1 );

		if ( $get_attached_file ) {
			$query->join(
				[ 'meta_value', 'post_id' ],
				DBQuery::tbl( new PostMetaQuery( 'file' ) )->select( [ 'attached_file' => 'meta_value' ] )->where( [
					'meta_key' => '_wp_attached_file'
				] )
			);
		}

		return $query;

	}

	static function get_metadata_thumbnails( $post_ids, $meta_key = '_thumbnail_id', $get_attached_file = false ) {
		return self::get_query_thumbnails_request( $post_ids, $meta_key, $get_attached_file )->get_results();
	}

}
