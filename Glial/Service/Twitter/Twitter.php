<?php

namespace Glial\Service\Twitter;

class Twitter
{
  
  private $app_details = array();
  
  public function createUserSession($credentials){

  $_SESSION['user_id'] = $credentials["user_id"];
  $_SESSION['user_token'] = $credentials["oauth_token"];
  $_SESSION['user_secret'] = $credentials["oauth_token_secret"];

  $this->initializeTweetScreen();
}


public function initializeTweetScreen($message = ''){

  $html = "<div>".$message."</div>
           <form action='' method='POST' >
             <textarea name='post_tweet' id='post_tweet'></textarea>
             <input name='submit_tweet' type='submit' value='Tweet' />
            </form>";

  echo $html;
}


public function initializeTwitterObject() {

    $this->app_details = array(
        'consumer_key' => $this->config['consumer_key'],
        'consumer_secret' => $this->config['consumer_secret'],
        'user_token' => $_SESSION['user_token'],
        'user_secret' => $_SESSION['user_secret']
    );


    $twitter_auth = new tmhOAuth($this->app_details);
    return $twitter_auth;
}

public function getContestResults() {

    $twitter_auth = $this->initializeTwitterObject();
    $search_params = array('q' => '#SPC #twcontesttutorial -RT', 'count' => 100, 'result_type' => 'recent');
    $this->retrieveTweets($twitter_auth, $search_params);

    $contest_results = array();
    $html = "<table>";
    $html .= "<tr><td>Username</td><td>Retweets</td></tr>";

    foreach ($this->result_tweets as $key => $value) {
        $username = $value->user->screen_name;
        $contest_results[$username] = isset($contest_results[$username]) ? ($contest_results[$username] + $value->retweet_count) : $value->retweet_count;
    }

    foreach ($contest_results as $key => $res) {
        $html .= "<tr><td>" . $key . "</td><td>" . $res . "</td></tr>";
    }

    $html .= "</table>";
    echo $html;
}


public function retrieveTweets($twitter_auth, $search_params) {

    $code = $twitter_auth->request('GET', $twitter_auth->url('1.1/search/tweets'), $search_params);
    $response = json_decode($twitter_auth->response["response"]);

    foreach ($response->statuses as $value) {
        array_push($this->result_tweets, $value);
    }

    if (isset($response->search_metadata->next_results) && count($this->result_tweets) < 500) {

        $search_meta = substr($response->search_metadata->next_results, 1);
        $search_meta = explode("&", $search_meta);
        $max_id = 0;
        foreach ($search_meta as $sm) {
            $max_id_res = explode("=", $sm);
            if ($max_id_res[0] == 'max_id') {
                $max_id = $max_id_res[1];
            }
        }

        $search_params['max_id'] = $max_id;
        $this->retrieveTweets($twitter_auth, $search_params);
    }
    
    
}

/**
 * 
 * Author : Fabien POTENCIER
 * 
 */
 
 
public function tweet($message, $username, $password)
{
  $context = stream_context_create(array(
    'http' => array(
      'method'  => 'POST',
      'header'  => sprintf("Authorization: Basic %s\r\n", base64_encode($username.':'.$password)).
                   "Content-type: application/x-www-form-urlencoded\r\n",
      'content' => http_build_query(array('status' => $message)),
      'timeout' => 5,
    ),
  ));
  $ret = file_get_contents('http://twitter.com/statuses/update.xml', false, $context);
 
  return false !== $ret;
}

/**
 * 
 * Author : EllisGL  
 * 
 */
 
function postTweet($message, $username, $password) 
{ 
$url = 'http://twitter.com/statuses/update.json'; 
$fld = http_build_query(array('status' => $message)); 
$ch = curl_init(); 

curl_setopt($ch, CURLOPT_URL, $url); 
curl_setopt($ch, CURLOPT_USERPWD, $username.':'.$password); 
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC); 
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
curl_setopt($ch, CURLOPT_POST, 1); 
curl_setopt($ch, CURLOPT_POSTFIELDS, $fld); 

$ret = curl_exec($ch); 

return false !== $ret; 
}
 
  
}





?>
