<?php

namespace Glial\Service\Webex;

class Webex
{
    public $data = array();
    public $id_group = 0;
    private $webExID;
    private $password;
    private $siteID;
    private $partnerID;
    private $siteURL;

    public function __construct($webExID, $password, $siteID, $partnerID, $siteURL)
    {
        $this->webExID = $webExID;
        $this->password = $password;
        $this->siteID = $siteID;
        $this->partnerID = $partnerID;
        $this->siteURL = ereg_replace("(https?)://", "", $siteURL);
    }

    private function transmit($payload)
    {
        // Generate XML Payload
        $xml = '<serv:message xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
        $xml .= '<header>';
        $xml .= '<securityContext>';
        $xml .= '<webExID>' . $this->webExID . '</webExID>';
        $xml .= '<password>' . $this->password . '</password>';
        $xml .= '<siteID>' . $this->siteID . '</siteID>';
        $xml .= '<partnerID>' . $this->partnerID . '</partnerID>';
        $xml .= '</securityContext>';
        $xml .= '</header>';
        $xml .= '<body>';
        $xml .= '<bodyContent xsi:type="java:com.webex.service.binding.' . $payload['service'] . '">';
        $xml .= $payload['xml'];
        $xml .= '</bodyContent>';
        $xml .= '</body>';
        $xml .= '</serv:message>';

        //pre($xml);
        // Separate $siteURL into Host and URI for Headers
        $host = substr($this->siteURL, 0, strpos($this->siteURL, "/"));
        $uri = strstr($this->siteURL, "/");

        // Generate Request Headers
        $content_length = strlen($xml);
        $headers = array(
            "POST $uri HTTP/1.0",
            "Host: $host",
            "User-Agent: PostIt",
            "Content-Type: application/x-www-form-urlencoded",
            "Content-Length: " . $content_length,
        );

        // Post the Request
        $ch = curl_init('https://' . $this->siteURL);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        $response = curl_exec($ch);

        return $response;
    }

    //public function user_AuthenticateUser()
    //public function user_CreateUser()
    //public function user_DelUser()
    //public function user_DelSessionTemplates()
    //public function user_GetloginTicket()
    //public function user_GetloginurlUser()
    //public function user_GetlogouturlUser()
    //public function user_GetUser()
    //public function user_LstsummaryUser()
    public function user_LstsummaryUser($startFrom = '1', $maximumNum = '', $listMethod = '', $orderOptions = '', $dateScope = '')
    {
        $xml = '<listControl>';
        if ($startFrom)
            $xml .= '<startFrom>' . $startFrom . '</startFrom>';
        if ($maximumNum)
            $xml .= '<maximumNum>' . $maximumNum . '</maximumNum>';
        if ($listMethod)
            $xml .= '<listMethod>' . $listMethod . '</listMethod>';
        $xml .= '</listControl>';

        if ($orderOptions) {
            $xml .= '<order>';
            foreach ($orderOptions as $options) {
                $xml .= '<orderBy>' . $options['By'] . '</orderBy>';
                $xml .= '<orderAD>' . $options['AD'] . '</orderAD>';
            }
            $xml .= '</order>';
        }

        if ($dateScope) {
            $xml .= '<dataScope>';
            if ($dateScope['regDateStart'])
                $xml .= '<regDateStart>' . $dateScope['regDateStart'] . '</regDateStart>';
            if ($dateScope['timeZoneID'])
                $xml .= '<timeZoneID>' . $dateScope['timeZoneID'] . '</timeZoneID>';
            if ($dateScope['regDateEnd'])
                $xml .= '<regDateEnd>' . $dateScope['regDateEnd'] . '</regDateEnd>';
            $xml .= '</dataScope>';
        }

        $payload['xml'] = $xml;
        $payload['service'] = str_replace("_", ".", __FUNCTION__);

        return $this->transmit($payload);
    }

    //public function user_SetUser()
    //public function user_UploadPMRIImage()
    //public function meeting_CreateMeeting();
    //public function meeting_CreateTeleconferenceSession();
    //public function meeting_DelMeeting();
    //public function meeting_GethosturlMeeting();
    //public function meeting_GetMeeting();
    //public function meeting_GetTeleconferenceSession();
    //public function meeting_LstsummaryMeeting();
    //public function meeting_SetMeeting();
    //public function meeting_SetTeleconferenceSession();
    //public function meeting_GetjoinurlMeeting()
    public function meeting_GetjoinurlMeeting($sessionKey, $attendeeName = '')
    {
        $xml = '<sessionKey>' . $sessionKey . '</sessionKey>';
        if ($attendeeName)
            $xml = '<attendeeName>' . $attendeeName . '</attendeeName>';

        $payload['xml'] = $xml;
        $payload['service'] = str_replace("_", ".", __FUNCTION__);

        return $this->transmit($payload);
    }

    //public function event_CreateEvent();
    //public function event_DelEvent();
    //public function event_GetEvent();
    //public function event_LstRecordedEvent();
    //public function event_LstsummaryProgram();
    public function event_LstsummaryProgram($programID = '')
    {
        if ($programID)
            $xml = '<programID>' . $programID . '</programID>';

        $payload['xml'] = (!empty($xml)) ? $xml : '';
        $payload['service'] = str_replace("_", ".", __FUNCTION__);

        return $this->transmit($payload);
    }

    //public function event_SendInvitationEmail();
    //public function event_SetEvent();
    //public function event_UploadEventImage();
    //public function event_LstsummaryEvent()
    public function event_LstsummaryEvent($startFrom = '1', $maximumNum = '', $listMethod = '', $orderOptions = '', $programID = '', $dateScope = '')
    {
        $xml = '<listControl>';
        if ($startFrom)
            $xml .= '<startFrom>' . $startFrom . '</startFrom>';
        if ($maximumNum)
            $xml .= '<maximumNum>' . $maximumNum . '</maximumNum>';
        if ($listMethod)
            $xml .= '<listMethod>' . $listMethod . '</listMethod>';
        $xml .= '</listControl>';

        if ($orderOptions) {
            $xml .= '<order>';
            foreach ($orderOptions as $options) {
                $xml .= '<orderBy>' . $options['By'] . '</orderBy>';
                $xml .= '<orderAD>' . $options['AD'] . '</orderAD>';
            }
            $xml .= '</order>';
        }

        if ($programID)
            $xml .= '<programID>' . $programID . '</programID>';

        if ($dateScope) {
            $xml .= '<dateScope>';
            if ($dateScope['startDateStart'])
                $xml .= '<startDateStart>' . $dateScope['startDateStart'] . '</startDateStart>';
            if ($dateScope['startDateEnd'])
                $xml .= '<startDateEnd>' . $dateScope['startDateEnd'] . '</startDateEnd>';
            if ($dateScope['endDateStart'])
                $xml .= '<endDateStart>' . $dateScope['endDateStart'] . '</endDateStart>';
            if ($dateScope['endDateEnd'])
                $xml .= '<endDateEnd>' . $dateScope['endDateEnd'] . '</endDateEnd>';
            $xml .= '</dateScope>';
        }

        $payload['xml'] = $xml;
        $payload['service'] = str_replace("_", ".", __FUNCTION__);

        return $this->transmit($payload);
    }

    /*
     *  Meeting Attendee Services
     */

    //public function attendee_CreateMeetingAttendee()
    public function attendee_CreateMeetingAttendee($attendees)
    {
        $xml = '';
        foreach ($attendees as $attendee) {
            //$xml .= '<attendees>';
            $xml .= '<person>';
            foreach ($attendee['info'] as $attr => $val) {
                if (!is_array($val) && !empty($val))
                    $xml .= '<' . $attr . '>' . $val . '</' . $attr . '>';

                if (is_array($val)) {
                    $xml .= '<' . $attr . '>';
                    foreach ($val as $att => $val) {
                        if (!empty($val))
                            $xml .= '<' . $att . '>' . $val . '</' . $att . '>';
                    }
                    $xml .= '</' . $attr . '>';
                }
            }
            $xml .= '</person>';

            foreach ($attendee['options'] as $attr => $val) {
                if (!empty($val))
                    $xml .= '<' . $attr . '>' . $val . '</' . $attr . '>';
            }
            //$xml .= '</attendees>';
        }

        $payload['xml'] = $xml;
        $payload['service'] = str_replace("_", ".", __FUNCTION__);

        return $this->transmit($payload);
    }

    //public function attendee_LstMeetingAttendee()
    public function attendee_LstMeetingAttendee($sessionKey)
    {
        $xml = '';
        $xml .= '<sessionKey>' . $sessionKey . '</sessionKey>';

        $payload['xml'] = $xml;
        $payload['service'] = str_replace("_", ".", __FUNCTION__);

        return $this->transmit($payload);
    }

    //public function attendee_RegisterMeetingAttendee()
    public function attendee_RegisterMeetingAttendee($attendees)
    {
        $xml = '';
        foreach ($attendees as $attendee) {
            $xml .= '<attendees>';
            $xml .= '<person>';
            foreach ($attendee['info'] as $attr => $val) {
                if (!is_array($val) && !empty($val))
                    $xml .= '<' . $attr . '>' . $val . '</' . $attr . '>';

                if (is_array($val)) {
                    $xml .= '<' . $attr . '>';
                    foreach ($val as $att => $val) {
                        if (!empty($val))
                            $xml .= '<' . $att . '>' . $val . '</' . $att . '>';
                    }
                    $xml .= '</' . $attr . '>';
                }
            }
            $xml .= '</person>';

            foreach ($attendee['options'] as $attr => $val) {
                if (!empty($val))
                    $xml .= '<' . $attr . '>' . $val . '</' . $attr . '>';
            }
            $xml .= '</attendees>';
        }

        $payload['xml'] = $xml;
        $payload['service'] = str_replace("_", ".", __FUNCTION__);

        return $this->transmit($payload);
    }

    //public function history_LstmeetingattendeeHistory()
    public function history_LsteventattendeeHistory($meetingKey = '', $orderOptions = '', $startTimeScope = '', $endTimeScope = '', $confName = '', $confID = '', $listControl = '', $inclAudioOnly = false)
    {
        $xml = '';

        if ($meetingKey)
            $xml .= '<meetingKey>' . $meetingKey . '</meetingKey>';

        if ($orderOptions) {
            $xml .= '<order>';
            foreach ($orderOptions as $options) {
                $xml .= '<orderBy>' . $options['By'] . '</orderBy>';
                $xml .= '<orderAD>' . $options['AD'] . '</orderAD>';
            }
            $xml .= '</order>';
        }

        if ($startTimeScope) {
            $xml .= '<startTimeScope>';
            $xml .= '<sessionStartTimeStart>' . $startTimeScope['sessionStartTimeStart'] . '</sessionStartTimeStart>';
            $xml .= '<sessionStartTimeEnd>' . $startTimeScope['sessionStartTimeEnd'] . '</sessionStartTimeEnd>';
            $xml .= '</startTimeScope>';
        }

        if ($endTimeScope) {
            $xml .= '<endTimeScope>';
            $xml .= '<sessionEndTimeStart>' . $endTimeScope['sessionEndTimeStart'] . '</sessionEndTimeStart>';
            $xml .= '<sessionEndTimeEnd>' . $endTimeScope['sessionEndTimeEnd'] . '</sessionEndTimeEnd>';
            $xml .= '</endTimeScope>';
        }

        if ($confName)
            $xml .= '<confName>' . $confName . '</confName>';

        if ($confID)
            $xml .= '<confID>' . $confID . '</confID>';

        if ($listControl) {
            $xml .= '<listControl>';
            $xml .= '<serv:startFrom>' . $listControl['startFrom'] . '</serv:startFrom>';
            $xml .= '<serv:maximumNum>' . $listControl['maximumNum'] . '</serv:maximumNum>';
            $xml .= '<serv:listMethod>' . $listControl['listMethod'] . '</serv:listMethod>';
            $xml .= '</listControl>';
        }

        if ($inclAudioOnly)
            $xml .= '<inclAudioOnly>' . $inclAudioOnly . '</inclAudioOnly>';

        $payload['xml'] = $xml;
        $payload['service'] = str_replace("_", ".", __FUNCTION__);

        return $this->transmit($payload);
    }

    public static function test_microsite($url)
    {
        $timeout = 10;

// Initialisation d'une session cURL
        $ch = curl_init($url);

// Forcer l'utilisation d'une nouvelle connexion (pas de cache)
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);

// Définition du timeout de la requête (en secondes)
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

// Si l'URL est en HTTPS
        if (preg_match('`^https://`i', $url)) {
            // Ne pas vérifier la validité du certificat SSL
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

// Suivre les redirections [facultatif]
// www.oseox.fr redirige par exemple automatiquement vers oseox.fr
// Le code de retour serait ici 301 si l'on ne suivait pas les redirections
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// Récupération du contenu retourné par la requête
// sous forme de chaîne de caractères via curl_exec()
// (directement affiché au navigateur client sinon)
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_NOBODY, true);

// Execution de la requête
        curl_exec($ch);

// Récupération du code HTTP retourné par la requête
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Fermeture de la session cURL
        curl_close($ch);

        return $http_code;
    }

    public static function xml_post($post_xml, $url, $port)
    {
        $timeout = 15;
        $user_agent = "Mozilla/5.0 (Windows NT 6.1; rv:14.0) Gecko/20100101 Firefox/14.0.1";

        $ch = curl_init();    // initialize curl handle
        curl_setopt($ch, CURLOPT_URL, $url); // set url to post to
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);  // Fail on errors
        //if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off'))
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);    // allow redirects
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
        curl_setopt($ch, CURLOPT_PORT, $port);          //Set the port number
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); // times out after 15s
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_xml); // add POST fields
        //curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);

        if ($port == 443) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        }
        $data = curl_exec($ch);

        curl_close($ch);

        return $data;
    }

    public static function clean_xml($answer)
    {
        $answer = str_replace('use:', '', $answer);
        $answer = str_replace('serv:', '', $answer);
        $answer = str_replace('xmlns:', '', $answer);
        $answer = str_replace('xsi:', '', $answer);
        $answer = str_replace('com:', '', $answer);

        return $answer;
    }

    public static function get_user_detail($sitename, $login, $password, $webex_id)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<serv:message xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:serv="http://www.webex.com/schemas/2002/06/service">
<header>
<securityContext>
<siteName>' . $sitename . '</siteName>
<webExID>' . $login . '</webExID>
<password>' . $password . '</password>
<partnerID>
</partnerID>
<email>
</email>
</securityContext>
</header>
<body>
<bodyContent xsi:type="java:com.webex.service.binding.user.GetUser">
<webExId>' . $webex_id . '</webExId>
</bodyContent>
</body>
</serv:message>';
        $url = "https://" . $sitename . ".webex.com/WBXService/XMLService";

        return self::xml_post($xml, $url, 443);
    }

    public static function get_microsite_users($sitename, $login, $password, $start_from, $nb_users)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<serv:message xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:serv="http://www.webex.com/schemas/2002/06/service">
<header>
<securityContext>
<siteName>' . $sitename . '</siteName>
<webExID>' . $login . '</webExID>
<password>' . $password . '</password>
<partnerID></partnerID>
<email></email>
</securityContext>
</header>
<body>
<bodyContent xsi:type="java:com.webex.service.binding.user.LstsummaryUser">
<listControl>
<serv:startFrom>' . $start_from . '</serv:startFrom>
<serv:maximumNum>' . $nb_users . '</serv:maximumNum>
<serv:listMethod>AND</serv:listMethod>
</listControl>
<order>
<orderBy>UID</orderBy>
<orderAD>ASC</orderAD>
</order>
</bodyContent>
</body>
</serv:message>';

        $url = self::generate_url($sitename);

        return self::xml_post($xml, $url, 443);
    }


    public static function test_answer($xml)
    {
        if ($xml) {
            $answer = self::clean_xml($xml);
            $tree = simplexml_load_string($answer);

            if (trim($tree->header->response->result) === "SUCCESS") {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public static function convert_xml_to_array($xml)
    {
        $xml = self::clean_xml($xml);
        $xml = simplexml_load_string($xml);

        $json = json_encode($xml);
        $array = json_decode($json, TRUE);

        return $array;
    }

    public static function generate_url($site_name)
    {
        return "https://" . $site_name . ".webex.com/WBXService/XMLService";
    }
    public static function clean_site_name($site_name)
    {
        $site_name = strtolower($site_name);

        $to_map = '.webex.com';

        if (stristr($site_name,$to_map)) {
            $out = explode($to_map,$site_name);
            $site_name = $out[0];
        }

        $to_map = '//';

        if (stristr($site_name,$to_map)) {
            $out = explode($to_map,$site_name);
            $site_name = $out[1];
        }

        return $site_name;
    }


    public static function get_cdrs($sitename, $login, $password, $date_start, $date_end)
    {
        $tab_date_start = explode("-",$date_start);
        $sessionStartTimeStart = $tab_date_start[1].'/'.$tab_date_start[0].'/'.$tab_date_start[2].' 00:00:00';
        $sessionStartTimeEnd = $tab_date_start[1].'/'.$tab_date_start[0].'/'.$tab_date_start[2].' 00:00:00';


        $tab_date_end = explode("-",$date_end);
        $sessionEndTimeStart = $tab_date_end[1].'/'.$tab_date_end[0].'/'.$tab_date_end[2].' 00:00:00';
        $sessionEndTimeEnd = $tab_date_end[1].'/'.$tab_date_end[0].'/'.$tab_date_end[2].' 00:00:00';





        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<serv:message xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:serv="http://www.webex.com/schemas/2002/06/service">
<header>
<securityContext>
<siteName>' . $sitename . '</siteName>
<webExID>' . $login . '</webExID>
<password>' . $password . '</password>
<partnerID></partnerID>
<email></email>
</securityContext>
</header>
<body>
<bodyContent xsi:type="java:com.webex.service.binding.history.LsteventattendeeHistory">
<startTimeScope>
<sessionStartTimeStart>'.$sessionStartTimeStart.'</sessionStartTimeStart>
<sessionStartTimeEnd>'.$sessionStartTimeEnd.'</sessionStartTimeEnd>
</startTimeScope>
<endTimeScope>
<sessionEndTimeStart>'.$sessionEndTimeStart.'</sessionEndTimeStart>
<sessionEndTimeEnd>'.$sessionEndTimeEnd.'</sessionEndTimeEnd>
</endTimeScope>
<listControl>
<startFrom>1</startFrom>
<maximumNum>10</maximumNum>
<listMethod>AND</listMethod>
</listControl>
<order>
<orderBy>ATTENDEENAME</orderBy>
<orderAD>ASC</orderAD>
<orderBy>STARTTIME</orderBy>
<orderAD>ASC</orderAD>
<orderBy>CONFID</orderBy>
<orderAD>ASC</orderAD>
</order>
</bodyContent>
</body>
</serv:message>';

        $url = self::generate_url($sitename);

        return self::xml_post($xml, $url, 443);
    }

}
