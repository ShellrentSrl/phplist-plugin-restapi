<?php

/**
 * Class Test_
 */
class TestRestapi extends \PHPUnit_Framework_TestCase 
{

    public $userId;
    public $loginName;
    public $password;
    public $url;

    function setUp() 
    {
        $this->loginName = 'admin';
        $this->password = 'phplist';
        // Set URL from constant stored in phpunit.xml
        $this->url = API_URL_BASE_PATH;
    }

    function tearDown() {
    }
    
    /**
     * Make a call to the API using cURL
     * @return string result of the CURL execution
     */
    private function callApi( $command, $post_params, $decode = true ) 
    {
        $post_params['cmd'] = $command;

        // Serialise and encode query
        $post_params = http_build_query( $post_params );
        
        // Prepare cURL
        $c = curl_init();
        curl_setopt( $c, CURLOPT_URL,            $this->url );
        curl_setopt( $c, CURLOPT_HEADER,         0 );
        curl_setopt( $c, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $c, CURLOPT_POST,           1 );
        curl_setopt( $c, CURLOPT_POSTFIELDS,     $post_params);
        // FIXME: this tmp path mustn't be hardcoded
        curl_setopt( $c, CURLOPT_COOKIEFILE,     '/tmp'.'/phpList_RESTAPI_Helper_cookiejar.txt');
        curl_setopt( $c, CURLOPT_COOKIEJAR,      '/tmp'.'/phpList_RESTAPI_Helper_cookiejar.txt');
        curl_setopt( $c, CURLOPT_HTTPHEADER,     array( 'Connection: Keep-Alive', 'Keep-Alive: 60' ) );
        
        // Execute the call
        $result = curl_exec( $c );

        // Check if decoding of result is required
        if ( $decode === true ) 
        {
            $result = json_decode( $result );
        }

        return $result;
    }

    /**
     * Use a real login to test login api call
     * @return bool true if user exists and login successful
     */
    function testLogin() 
    {
        // Set the username and pwd to login with
        $post_params = array(
            'login' => $this->loginName,
            'password' => $this->password
        );

        // Execute the login with the credentials as params
        $result = $this->callAPI( 'login', $post_params );
        
        // Check if the login was successful
        $this->assertEquals( 'success', $result->status );
        
    }

}

?>
