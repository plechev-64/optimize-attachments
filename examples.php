<?php

//posts IDs
$post_ids = [
	1, 2, 3
];

//setup thumbnails data of posts
$Thumbnails = OptAttachments::setup_post_thumbnails($post_ids);

//the example of loop
foreach($post_ids as $post_id){

	//the getting thumbanil url
	echo $Thumbnails->attachment($post_id)->get_url('thumbnail'); //the size can be a string or array [width, height]

	//the getting thumbnail image
	echo $Thumbnails->attachment($post_id)->get_image('thumbnail');

	//the getting thumbnail image by the short case
	echo $Thumbnails->get_thumbnail_image($post_id, 'thumbnail');

}

//we can specify a meta_key of custom thumbnail of posts by the second argument
//and force get an url of the attachment for more right the building source of an image if need
$Thumbnails = OptAttachments::setup_post_thumbnails($post_ids, 'custom_thumbnail', true);



