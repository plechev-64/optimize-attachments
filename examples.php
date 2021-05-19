<?php

//posts IDs
$post_ids = [
	1, 2, 3
];

//setup thumbnails data of posts
$Thumbnails = OptAttachments::setup_post_thumbnails($post_ids);

//the example of loop
foreach($post_ids as $post_id){

	if(!$Thumbnails->is_has($post_id))
		continue;

	//the getting a thumbnail url
	echo $Thumbnails->attachment($post_id)->get_url('thumbnail'); //the size can be a string or array [width, height]

	//the getting a thumbnail image
	echo $Thumbnails->attachment($post_id)->get_image('medium');

	//the getting an image withs attributes
	echo $Thumbnails->attachment($post_id)->get_image([125, 200], [
		'class' => 'foo bar',
		'title' => 'The name of this image',
	]);

	//the getting a thumbnail image by the short case
	echo $Thumbnails->get_thumbnail_image($post_id, 'thumbnail');

}

//we can specify a meta_key of custom thumbnail of posts by the second argument
//and force get an url of the attachment for more right the building source of an image if need
$Thumbnails = OptAttachments::setup_post_thumbnails($post_ids, 'custom_thumbnail', true);

/**
 * The getting custom attachments
 */

//Attachments IDs
$attach_ids = [
	1, 2, 3
];

$Attachments = OptAttachments::setup_attachments($attach_ids);

foreach($attach_ids as $attach_id){

	if(!$Attachments->is_has($attach_id))
		continue;

	echo $Attachments->attachment($attach_id)->get_image('medium');

}


