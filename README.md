*This project is in a very early stage and I know, that the code is far from being perfect, but I'll improve it constantly.*


This PHP class reads the statuses of a Facebook page and renders HTML code out of it. It recognizes images, links and youtube videos and embeds them reasonable. Everything can be styled with CSS (a basic stylesheet is included).

Many aspects of the class are configurable via an options array. Simply copy the $cfg Array from the class, remove values which already fit your needs and edit the remaining values. Use the setOptions() methode to hand over the options to the class then.

 ```php
require_once('FacebookWall.php');

$id = 'bbcnews';
$access_token = '326204564096805|TJBwx3q1wcOj62mPmN3K743K0us';

$fb = new FacebookWall($id, $access_token);

$cfg = array(
    'lang'          => 'de',		// Language to be used
    'numPosts'      => 40,			// Number of posts to be shown
    'showComments'  => false,		// Show comments beneath posts?
    'quotes'        => false,		// Wrap post text in quotation marks?
);
$fb->setOptions($cfg);

echo $fb->render();
```
