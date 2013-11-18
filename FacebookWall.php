<?php

class FacebookWall {

    private $facebookId;
    private $accessToken;

    // Output buffer
    private $html;

    // Config
    private $cfg = array(
        'lang'          => 'en',        // Language
        'langPath'      => 'lang/',     // Path of i18n files
        'numPosts'      => 8,          // Number of posts to be shown
        'limitLikes'    => 500,        // Maximal shown likes (experimental)
        'limitComments' => 50,          // Maximal shown comments (experimental)
        'justOwnPosts'  => true,        // Show posts of other persons on feed as well?
        'showLikes'     => true,        // Show number of likes beneath post?
        'showComments'  => true,        // Show comments beneath posts?
        'showDate'      => true,        // Show date of post beneath?
        'hyphenate'     => false,       // Use Hyphenator.js? https://code.google.com/p/hyphenator
        'quotes'        => false,        // Wrap post text in quotation marks?
        'youtube'       => array(       // See https://developers.google.com/youtube/player_parameters
            'vq'              => 'large',
            'modestbranding'  => 1,
            'showinfo'        => 0,
            'autohide'        => 1,
            'wmmode'          => 'transparent',
            'html5'           => 1
        )
    );

    private $post;
    private $postId;

    /**
     * Constructor
     * @param mixed  $id    Numeric Facebook ID of user or page
     * @param string $token Facebook access token
     */
    function __construct($id = null, $token = null) {
        if (isset($id)) {
            $this->facebookId = $id;
        }

        if (isset($token)) {
            $this->accessToken = $token;
        }

		$this->setupLocale();

        $this->html = '<div id="fb-wall">';
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
		$this->setupLocale();
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
     * Main method: Gathers data from Facebook wall and renders HTML markup
     * @return boolean false on error, HTML markup on success
     */
    public function render() {

        // Require i18n file
        require_once($this->cfg['langPath'] . $this->cfg['lang'] . '.lang.php');

        try {
            $data = $this->retrieveData();
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }

        foreach ($data as $this->post) {
            $this->postId = $this->getPostId();

            // Skip entries with no message
            if (empty($this->post->message)) {
                continue;
            }

            // Skip foreign posts
			// FIXME: Make this configurable
            if ($this->cfg['justOwnPosts'] && $this->post->from->id !== $this->facebookId) {
                continue;
            }

            // Initializing markup
            if ($this->cfg['hyphenate']) {
                $postClass = ' hyphenate';
            } else {
                $postClass = null;
            }

            $this->html .= '<div class="fb-post' . $postClass .'">';

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
            // $this->insertLikes(); // List of likes, not working right now

            if ($this->cfg['showComments']) {
                $this->insertComments();
            }

            $this->insertFooter();

            $this->html .= '</div>';
        }
        $this->html .= '</div>';

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
	 * Sets up locale
	 * @return bool status
	 */
	private function setupLocale() {
		if (!isset($this->cfg['lang'])) {
			return false;
		}

		switch ($this->cfg['lang']) {
			default:
			case 'en':
				setlocale (LC_ALL, 'en_US.utf8');
				break;
			case 'de':
				setlocale (LC_ALL, 'de_DE.utf8');
				break;
		}

		return true;
	}

    /**
     * Retrieves data from a specific facebook wall
     * @throws Exception
     * @return object An object which contains last posts
     */
    private function retrieveData() {
        if (empty($this->facebookId)) {
            throw new Exception('Error: Facebook id not set!');
        }
        if (empty($this->accessToken)) {
            throw new Exception('Error: Access token not set!');
        }

        $url = 'https://graph.facebook.com/' . $this->facebookId;
        $url .= '?fields=posts.limit(' . $this->cfg['numPosts'] . ').fields(';
		$url .= 'id,';
		$url .= 'name,';
		$url .= 'from,';
		$url .= 'to,';
		$url .= 'message,';
		$url .= 'comments.limit(' . $this->cfg['limitComments'] . '),';
		$url .= 'likes.limit(' . $this->cfg['limitLikes'] . '),';
		$url .= 'picture,';
		$url .= 'link,';
		$url .= 'created_time,';
		$url .= 'type';
        $url .= ')&access_token=' . $this->accessToken;

        $wall = json_decode(file_get_contents($url));

        if (empty($wall)) {
            throw new Exception('Error while retrieving Facebook data');
        }

        // Overwrite with numerical id
        $this->facebookId = $wall->id;

        return $wall->posts->data;
    }

    /**
     * Sets postId attribute up
     * @return string post id
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
        $this->html .= '<pre>';

        if (isset($this->post->from) && isset($this->post->to)) {
            $this->html .= '<a href="https://facebook.com/' . $this->post->from->id . '"
                title="Facebook ' . PROFILE .'" target="_blank">' . $this->post->from->name . '</a>: ';
        }

        if ($this->cfg['quotes']) {
            $this->html .= '“' . $this->autolink($this->post->message) . '”';
        } else {
            $this->html .= $this->autolink($this->post->message);
        }

        $this->html .= '</pre>';

    }

    /**
     * Inserts footer of post in markup
     * @return void
     */
    private function insertFooter() {
        $this->html .= '
            <footer>
        ';

        // Number of likes
        if ($this->cfg['showLikes']) {
            $this->html .= '
                <span>
                    <span aria-hidden="true" class="icon-likes" title="' . LIKES . '"></span>' . $this->getNumLikes() . '
                </span>
            ';
        }

        // Show comments button
        if ($this->cfg['showComments'] && isset($this->post->comments)) {
            $this->html .= '
                <span>
                    <span aria-hidden="true" class="icon-comments"></span>
                    <a href="javascript:void(0)" class="showComments">' . SHOW_COMMENTS . '</a>
            ';

            $numOfComments = count($this->post->comments->data);
            if ($numOfComments == $this->cfg['limitComments']) {
                $numOfComments = ($numOfComments - 1) . '+';
            }

            $this->html .= '(' . $numOfComments . ')';
            $this->html .= '
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
                <img class="news-image" src="' . $img . '" />
            </a>
        ';
    }

    /**
     * Inserts a post video in markup (YouTube)
	 * FIXME: This only works if YouTube videoId is set in GET parameter 'v'
     * @return void
     */
    private function insertVideo() {
        parse_str(parse_url($this->post->link, PHP_URL_QUERY), $urlParams);

        if (isset($this->post->name)) {
            $this->html .= '
                <h2>
                    <a href="' . $this->post->link . '" title="YouTube Link" target="_blank">' . $this->post->name . '</a>
                </h2>
            ';
        }

		if (isset($urlParams['v'])) {
			$this->html .= '
				<iframe class="youtube" src="http://www.youtube.com/embed/' . $urlParams['v'] . '?' . $this->parseYoutubeOptions() . '" allowfullscreen></iframe>
			';
		}
    }

    /**
     * Inserts list of likes in markup
     * This is rather experimental and disabled right now...
     * @return void
     */
    private function insertLikes() {
        if (isset($this->post->likes->data)) {
            $this->html .= '<div class="likes" style="display: none">';
            foreach ($this->post->likes->data as $person) {
                $link = '<a href="https://facebook.com/' . $person->id . '" target="_blank" title="Facebook ' . PROFILE . '">' . $person->name . '</a>';
                $this->html .= '<span class="like">' . $link . '</span>';
                if ($person !== end($this->post->likes->data)) {
                    $this->html .= ', ';
                }
            }
            $this->html .= '</div>';
        }
    }

    /**
     * Inserts comments of a post in markup
     * @return void
     */
    private function insertComments() {
        // echo '<pre>';
        // print_r($this->post->comments);
        // echo '</pre>';

        if (isset($this->post->comments)) {
            $this->html .= '<div class="comments" style="display: none;"><ul>';
            foreach ($this->post->comments->data as $comment) {
                $from = '<a href="https://facebook.com/' . $comment->from->id . '" target="_blank" title="Facebook ' . PROFILE . '">' . $comment->from->name . '</a>';
                $this->html .= '
                    <li>
                        <div class="comment">
                            <div class="comment-body">' .
                                $from . ': “' . $comment->message . '”
                            </div>
                            <div class="comment-date">
                                <span>' . $this->formatDate($comment->created_time) . '</span>
                            </div>
                        </div>
                    </li>
                ';
            }
            $this->html .= '</ul></div>';
        }
    }

    /**
     * Inserts date of post and links it to original post
     * @return void
     */
    private function insertPostDate() {
        $postLink = 'https://www.facebook.com/' . $this->facebookId . '/posts/' . $this->postId;
        $postDate = $this->formatDate($this->post->created_time);

        $this->html .= '
                <a href="' . $postLink . '" class="date" title="' . ORIGINAL_POST . ' Facebook" target="_blank">
                    <span>' . POSTED_ON . ' ' . $postDate . '</span>
                </a>
            </footer>
        ';
    }

    /**
     * Get number of likes
     * @return mixed Number of likes
     */
    private function getNumLikes() {
        if (!isset($this->post->likes)) {
            return 0;
        } else {
            $num = count($this->post->likes->data);
            if ($num == $this->cfg['limitLikes']) {
                return (string) ($this->cfg['limitLikes'] - 1) . '+';
            } else {
                return count($this->post->likes->data);
            }
        }
    }


    /**
     * Scans given string for hyperlinks, and links them via HTML
     * Uses target='_blank'
	 * TODO: Use different label for different types of links (Facebook event, external, ...)
     * @param string A string which may contain hyperlinks
     * @return string HTML markup
     */
    private function autolink($str) {
        $pattern = '#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#';
        return preg_replace($pattern , '<a href="\1" target="_blank" title="' . EXTERNAL_LINK . '">Link</a>', $str);
    }

    /**
     * Returns given datetime in locale specific format
     * @param  string $date mySQL datetime
     * @param  string $lang country code
     * @return string       formatted date
     */
    private function formatDate($date) {
		$time = strtotime($date);

        switch ($this->cfg['lang']) {
            case 'de':
                $date = date('j.', $time) . ' ' . strftime('%B', $time) . ' um '  . date('H:m', $time);
                break;
            case 'en':
            default:
                $date = strftime('%B', $time) . ' ' . date('jS \a\t G:m', $time);
                break;
        }

        return $date;

    }

    /**
     * Parses given options for embedded YouTube videos
     * @return string: GET parameters for embedding url
     */
    private function parseYoutubeOptions() {
        $urlParams = '';
        $i = 1;
        foreach ($this->cfg['youtube'] as $key => $value) {
            $urlParams .= $key . '=' . $value;
            if ($i < count($this->cfg['youtube'])) {
                $urlParams .= '&amp;';
            }
            $i++;
        }

        return $urlParams;
    }

}