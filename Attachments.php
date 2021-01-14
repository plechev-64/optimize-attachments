<?php

class Attachment{

	public $attach_id = 0;
	public $metadata = [];

	function __construct($attach_id, $metadata){
		$this->attach_id = $attach_id;
		$this->metadata = $metadata;
	}

	function get_url($size){
		$imageData = $this->get_image_data($size);
		return get_home_url(null, 'wp-content/uploads/'.$imageData['path']);
	}

	function get_image( $size = 'thumbnail', $attr = '' ) {
		$html  = '';

		$src = $this->get_url($size);
		$imagedata = $this->metadata;

		$width = $imagedata['width'];
		$height = $imagedata['height'];

		$hwstring   = image_hwstring( $width, $height );
		$size_class = $size;

		if ( is_array( $size_class ) ) {
			$size_class = implode( 'x', $size_class );
		}

		$default_attr = array(
			'src'   => $src,
			'class' => "attachment-$size_class size-$size_class",
			//'alt'   => trim( strip_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) ),
		);

		if ( wp_lazy_loading_enabled( 'img', 'wp_get_attachment_image' ) ) {
			$default_attr['loading'] = 'lazy';
		}

		$attr = wp_parse_args( $attr, $default_attr );

		if ( array_key_exists( 'loading', $attr ) && ! $attr['loading'] ) {
			unset( $attr['loading'] );
		}

		if ( empty( $attr['srcset'] ) ) {
			if ( is_array( $imagedata ) ) {

				$size_array = array( absint( $width ), absint( $height ) );
				$srcset     = wp_calculate_image_srcset( $size_array, $src, $imagedata, $this->attach_id );
				$sizes      = wp_calculate_image_sizes( $size_array, $src, $imagedata, $this->attach_id );

				if ( $srcset && ( $sizes || ! empty( $attr['sizes'] ) ) ) {
					$attr['srcset'] = $srcset;

					if ( empty( $attr['sizes'] ) ) {
						$attr['sizes'] = $sizes;
					}
				}
			}
		}

		$attr = array_map( 'esc_attr', $attr );
		$html = rtrim( "<img $hwstring" );

		foreach ( $attr as $name => $value ) {
			$html .= " $name=" . '"' . $value . '"';
		}

		$html .= ' />';

		return $html;
	}

	private function get_image_data( $size = 'thumbnail' ) {

		if ( ! $size || ! is_array( $this->metadata ) || empty( $this->metadata['sizes'] ) ) {
			return false;
		}

		$imagedata = $this->metadata;

		$data = array();

		if(!isset( $imagedata['sizes']['full'] ) && isset($imagedata['height'])){
			$imagedata['sizes']['full'] = [
				'width' => $imagedata['width'],
				'height' => $imagedata['height'],
				'file' => str_replace(dirname( $imagedata['file'] ).'/', '', $imagedata['file']),
			];
		}

		// Find the best match when '$size' is an array.
		if ( is_array( $size ) ) {
			$candidates = array();

			if ( ! isset( $imagedata['file'] ) && isset( $imagedata['sizes']['full'] ) ) {
				$imagedata['height'] = $imagedata['sizes']['full']['height'];
				$imagedata['width']  = $imagedata['sizes']['full']['width'];
			}

			foreach ( $imagedata['sizes'] as $_size => $data ) {
				// If there's an exact match to an existing image size, short circuit.
				if ( (int) $data['width'] === (int) $size[0] && (int) $data['height'] === (int) $size[1] ) {
					$candidates[ $data['width'] * $data['height'] ] = $data;
					break;
				}

				// If it's not an exact match, consider larger sizes with the same aspect ratio.
				if ( $data['width'] >= $size[0] && $data['height'] >= $size[1] ) {
					// If '0' is passed to either size, we test ratios against the original file.
					if ( 0 === $size[0] || 0 === $size[1] ) {
						$same_ratio = wp_image_matches_ratio( $data['width'], $data['height'], $imagedata['width'], $imagedata['height'] );
					} else {
						$same_ratio = wp_image_matches_ratio( $data['width'], $data['height'], $size[0], $size[1] );
					}

					if ( $same_ratio ) {
						$candidates[ $data['width'] * $data['height'] ] = $data;
					}
				}
			}

			if ( ! empty( $candidates ) ) {
				// Sort the array by size if we have more than one candidate.
				if ( 1 < count( $candidates ) ) {
					ksort( $candidates );
				}

				$data = array_shift( $candidates );

			} elseif ( ! empty( $imagedata['sizes']['thumbnail'] ) && $imagedata['sizes']['thumbnail']['width'] >= $size[0] && $imagedata['sizes']['thumbnail']['width'] >= $size[1] ) {
				$data = $imagedata['sizes']['thumbnail'];
			} else {
				return false;
			}

			// Constrain the width and height attributes to the requested values.
			list( $data['width'], $data['height'] ) = image_constrain_size_for_editor( $data['width'], $data['height'], $size );

		} elseif ( ! empty( $imagedata['sizes'][ $size ] ) ) {
			$data = $imagedata['sizes'][ $size ];
		}

		// If we still don't have a match at this point, return false.
		if ( empty( $data ) ) {

			return false;
		}

		// Include the full filesystem path of the intermediate file.
		if ( empty( $data['path'] ) && ! empty( $data['file'] ) && ! empty( $imagedata['file'] ) ) {
			//$file_url     = wp_get_attachment_url( $post_id );
			$data['path'] = path_join( dirname( $imagedata['file'] ), $data['file'] );
			//$data['url']  = path_join( dirname( $file_url ), $data['file'] );
		}

		return $data;

	}

}

class Attachments{

	public $attachments = [];

	function __construct($attachments){
		$this->attachments = $attachments;
	}

	function is_has($id){
		return isset($this->attachments[$id]);
	}

	function attachment($id){
		return $this->attachments[$id];
	}

	function get_thumbnail_image($post_id, $size, $atts = []){
		if($this->is_has($post_id)){
			return $this->attachment($post_id)->get_image($size, $atts);
		}else{
			return get_the_post_thumbnail( $post_id, $size, $atts);
		}
	}

	static function setup_post_thumbnails($post_ids, $meta_key = '_thumbnail_id'){

		$attachments = [];
		if($thumbnails = self::get_metadata_thumbnails($post_ids, $meta_key)){
			foreach($thumbnails as $thumbnail){
				$attachments[$thumbnail->post_id] = new Attachment($thumbnail->attach_id, maybe_unserialize($thumbnail->metadata));
			}
		}

		return new Attachments($attachments);

	}

	static function setup_attachments($attach_ids){

		$attachments = [];
		if($thumbnails = self::get_metadata_attachments($attach_ids)){
			foreach($thumbnails as $thumbnail){
				$attachments[$thumbnail->attach_id] = new Attachment($thumbnail->attach_id, maybe_unserialize($thumbnail->metadata));
			}
		}

		return new Attachments($attachments);

	}

	static function get_query_attachments_request($attach_ids){

		return DBQuery::tbl(new Postmeta_Query())->select([
			'attach_id' => 'post_id',
			'metadata' => 'meta_value'
		])->where([
			'meta_key' => '_wp_attachment_metadata',
			'post_id__in' => array_diff(array_unique($attach_ids), ['']),
		])->limit(-1);

	}

	static function get_metadata_attachments($attach_ids){
		return self::get_query_attachments_request($attach_ids)->get_results();
	}

	static function get_query_thumbnails_request($post_ids, $meta_key = '_thumbnail_id'){

		return DBQuery::tbl(new Postmeta_Query())->select([
			'post_id',
			'attach_id' => 'meta_value'
		])->where([
			'meta_key' => $meta_key,
			'post_id__in' => $post_ids,
		])->join(
			['meta_value', 'post_id'],
			DBQuery::tbl(new Postmeta_Query('metadata'))->select(['metadata' => 'meta_value'])->where([
				'meta_key' => '_wp_attachment_metadata'
			])
		)->limit(-1);

	}

	static function get_metadata_thumbnails($post_ids, $meta_key = '_thumbnail_id'){
		return self::get_query_thumbnails_request($post_ids, $meta_key)->get_results();
	}

}
