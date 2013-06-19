<?php

class FacebookWall {

	private $facebookId;
	private $accessToken;

	// Output buffer
	private $html = '';

	private $cfg = array(
		'lang'		   	=> 'en',
		'langPath'		=> 'lang/',
		'numPosts'	   	=> 15,
		'justOwnPosts' 	=> true,
		'showLikes'	   	=> true,
		'showComments' 	=> true,
		'showDate' 	   	=> true,
		'hyphenate'    	=> false,
		'quotes'		=> true,
		'youtube'	   	=> array(
			'vq'		 		=> 'large',
			'modestbranding'	=> 1,
			'showinfo'			=> 0,
			'autohide'			=> 1,
			'wmmode'			=> 'transparent',
			'html5'				=> 1
		)
	);

	private $post;
	private $postId;

	/**
	 * C'tor
	 * @param mixed  $id    Facebook ID of user or page
	 * @param string $token Facebook access token
	 */
	function __construct($id = null, $token = null) {
		if (isset($id)) {
			$this->facebookId = $id;
		}

		if (isset($token)) {
			$this->accessToken = $token;
		}

		if (isset($num)) {
			$this->cfg['numPosts'] = $num;
		}
	}

	public function setFacebookId($id) {
		$this->facebookId = $id;
	}	

	public function setAccessToken($token) {
		$this->accessToken = $token;
	}

	public function setLanguage($lang) {
		$available = array('en', 'de');
		if (in_array($lang, $available)) {
			$this->cfg['lang'] = $lang;
		}
	}

	/**
	 * Sets options
	 * @param array $cfg Associative array with key value pairs of options
	 */
	public function setOptions(array $cfg) {
		foreach ($cfg as $key => $value) {
			if (array_key_exists($key, $this->cfg)) {
				$this->cfg[$key] = $value;
			}
		}
	}
	
	/**
	 * Main methode: Gathers data from Facebook wall and renders HTML markup
	 * @return string complete HTML markup
	 */
	public function render() {

		require_once($this->cfg['langPath'] . $this->cfg['lang'] . '.lang.php');

		foreach ($this->retrieveData() as $post) {
			$this->post = $post;
			$this->postId = $this->getPostId();
			unset($post);	
			
			// Skip entries with no message or foreign posts
			if (empty($this->post->message) || $this->post->from->id !== $this->facebookId) {
				continue;
			}

			// Initializing markup
			if ($this->cfg['hyphenate']) {
				$addClass = ' hyphenate';
			} else {
				$addClass = null;
			}

			$this->html .= '<section class="news-entry' . $addClass .'">';

			// Looking for special posts
			switch ($this->post->type) {
				case 'photo':
					$this->insertImage();
					break;
				
				case 'video':
					$this->insertVideo();
					break;
			}

			$this->insertMessage();

			if ($this->cfg['showComments']) {
				$this->insertComments();
			}

			$this->insertFooter();

			$this->html .= '</section>';

			$this->post = null;
		}

		// Ready HTML markup
		return $this->html;
	}

	/**
	 * Dumps raw data structure (as retrieved from facebook)
	 * (For debugging purposes)
	 * @return string print_r of data array
	 */
	public function rawDump() {
		ob_start();
		echo '<pre>';
		echo print_r($this->retrieveData());
		echo '</pre>';
		ob_end_flush();
	}

	/**
	 * Retrieves data from a specific facebook wall
	 * @return object An object which contains last posts
	 */
	private function retrieveData() {
		$url = 'https://graph.facebook.com/' . $this->facebookId . '/feed?access_token=' . $this->accessToken . '&limit=' . $this->cfg['numPosts'];
		$obj = json_decode(file_get_contents($url));
		return $obj->data;
	}

	/**
	 * Sets postId attribute up
	 * @return void
	 */
	private function getPostId() {
		$tmp = explode('_', $this->post->id);
		return $tmp[1];
	}

	/**
	 * Inserts post message in markup
	 * @return void
	 */
	private function insertMessage() {
		$this->html .= '<p>';

		if (isset($this->post->from) && isset($this->post->to)) {
			$this->html .= '<a href="https://facebook.com/' . $this->post->from->id . '" 
				title="Facebook ' . PROFILE .'" target="_blank">' . $this->post->from->name . '</a>: ';
		}

		if ($this->cfg['quotes']) {
			$this->html .= '“' . $this->autolink($this->post->message) . '”';
		} else {
			$this->html .= $this->autolink($this->post->message);
		}

		$this->html .= '</p>';
		
	}

	/**
	 * Inserts footer of post in markup	
	 * @return void
	 */
	private function insertFooter() {
		$this->html .= '
			<footer>
		';

		if ($this->cfg['showLikes']) {
			$this->html .= '
				<span>
					<span aria-hidden="true" class="icon-likes" title="' . LIKES . '"></span>' . $this->getNumLikes() . '
				</span>
			';	
		}		

		// Show comments button
		if ($this->cfg['showComments'] && $this->post->comments->count > 0) {
			$this->html .= '
				<span>
					<span aria-hidden="true" class="icon-comments"></span>
					<a href="#" class="showComments">' . SHOW_COMMENTS . '</a> (' . $this->post->comments->count . ')
				</span>
			';
		}

		$this->insertPostDate();		
	}

	/**
	 * Inserts a post image in markup
	 * @return void
	 */
	private function insertImage() {
		// Take larger version of image
		$img = str_replace('_s.jpg', '_n.jpg', $this->post->picture);
		$this->html .= '
			<a href="' . $this->post->link . '" title="' . SEE_ON . ' Facebook" target="_blank">
				<div class="news-image fancy-image left" style="background-image: url(' . $img . ')"></div>
			</a>
		';
	}

	/**
	 * Inserts a post video in markup (YouTube)
	 * @return void
	 */
	private function insertVideo() {
		parse_str(parse_url($this->post->link, PHP_URL_QUERY), $urlParams);
		$this->html .= '
			<h2>
				<a href="' . $this->post->link . '" title="YouTube Link" target="_blank">' . $this->post->name . '</a>
			</h2>
			<iframe class="youtube" src="http://www.youtube.com/embed/' . $urlParams['v'] . '?' . $this->parseYoutubeOptions() . '" allowfullscreen></iframe>
		';
	}

	/**
	 * Inserts comments of a post in markup
	 * @return void
	 */
	private function insertComments() {
		if ($this->post->comments->count > 0) {
			$this->html .= '<section class="comments"><ul>';
			foreach ($this->post->comments->data as $comment) {
				$from = '<a href="https://facebook.com/' . $comment->from->id . '" target="_blank" title="Facebook ' . PROFILE . '">' . $comment->from->name . '</a>';
				$this->html .= '
					<li><div class="comment">' . $from . ': “' . $comment->message . '”</div></li>
				';
			}
			$this->html .= '</ul></section>';
		}
	}

	/**
	 * Inserts date of post and links it to original post
	 * @return void
	 */
	private function insertPostDate() {
		$postLink = 'https://www.facebook.com/' . $this->facebookId . '/posts/' . $this->postId;		
		$postDate = $this->formatDate($this->post->created_time, $this->cfg['lang']);

		$this->html .= '
				<a href="' . $postLink . '" class="date" title="' . ORIGINAL_POST . ' Facebook" target="_blank">
					<span>' . POSTED_ON . ' ' . $postDate . '</span>
				</a>
			</footer>			
		';
	}

	/**
	 * Get number of likes
	 * @return int Number of likes
	 */
	private function getNumLikes() {
		if (empty($this->post->likes->count)) {
			return 0;
		} else {
			return $this->post->likes->count;
		}
	}


	/**
	 * Scans given string for hyperlinks, and links them via HTML
	 * Uses target='_blank'
	 * @return string HTML markup
	 */
	private function autolink($str) {
		$pattern = '#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#';
		return preg_replace($pattern , '<a href="\1" target="_blank" title="' . EXTERNAL_LINK . '">Link</a>', $str);
	}

	/**
	 * Returns given datetime in language specific format
	 * @param  string $date mySQL datetime
	 * @param  string $lang country code
	 * @return string       formatted date
	 */
	private function formatDate($date, $lang) {
		switch ($lang) {
			case 'de':
				return date('d.m.Y - H:m', strtotime($date));
				break;			
			case 'en':
			default:
				return date('m/d/Y - H:m', strtotime($date));
				break;
		}
		
	}

	private function parseYoutubeOptions() {
		$urlParams = '';		
		
		$i = 1;
		$n = count($this->cfg['youtube']);

		foreach ($this->cfg['youtube'] as $key => $value) {
			$urlParams .= $key . '=' . $value;
			if ($i < $n) {
				$urlParams .= '&amp;';
			}
			$i++;
		}

		return $urlParams;
	}
	
}