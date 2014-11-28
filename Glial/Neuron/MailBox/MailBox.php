<?php

namespace Glial\Neuron\MailBox;

trait MailBox
{

    function mailbox($param)
    {
        $this->layout_name = "admin";

        $this->data['options'] = array("all_mails", "inbox", "sent_mail", "trash", "compose", "msg");
        $this->data['display'] = array("All mails", "Inbox", "Sent mail", "Trash", "Compose", "Message");

        $this->data['request'] = $param[0];
        $this->data['send_to'] = $param;

        $db = $this->di['db']->sql("default");
        $user = $this->di['auth']->getUser();



        if (!in_array($param[0], $this->data['options'])) {
            exit;
        }


        $sql = "SELECT * FROM user_main a
		INNER JOIN geolocalisation_country b ON a.id_geolocalisation_country = b.id
		INNER JOIN geolocalisation_city c ON a.id_geolocalisation_city = c.id
		
where a.id ='" . $db->sql_real_escape_string($user->id) . "'";
        $res = $db->sql_query($sql);

        $dfgwdfwdf = $db->sql_to_array($res);
        $this->data['user'] = $dfgwdfwdf[0];


        $i = 0;
        foreach ($this->data['options'] as $line) {
            if ($line === $this->data['request']) {
                $this->title = __($this->data['display'][$i]);

                $this->ariane = "> <a href=\"" . LINK . "user/\">" . __("Members") . "</a> > "
                        . '<a href="' . LINK . 'user/profil/' . $user->id . '">' . $user->firstname . ' ' . $user->name . '</a>'
                        . ' > ';

                ($this->data['request'] != "all_mails") ? $this->ariane .= '<a href="' . LINK . 'user/mailbox/all_mails">' . __('Mailbox') . '</a>' : $this->ariane .= __('Mailbox');
                ($this->data['request'] != "all_mails") ? $this->ariane .= ' > ' . $this->title : "";

                break;
            }
            $i++;
        }

        switch ($this->data['request']) {

            case "compose":
                if ($_SERVER['REQUEST_METHOD'] == "POST") {



                    if (!empty($_POST['mailbox_main']['id_user_main__to'])) {
                        $data = array();
                        $data['mailbox_main'] = $_POST['mailbox_main'];
                        $data['mailbox_main']['date'] = date('c');
                        $data['mailbox_main']['id_user_main__box'] = $user->id;
                        $data['mailbox_main']['id_user_main__from'] = $user->id;
                        $data['mailbox_main']['id_mailbox_etat'] = 2;
                        $data['mailbox_main']['id_history_etat'] = 1;

                        if ($db->sql_save($data)) {
                            $data['mailbox_main']['id_user_main__box'] = $_POST['mailbox_main']['id_user_main__to'];
                            if ($db->sql_save($data)) {

                                //send mail
                                I18n::SetDefault("en");
                                I18n::load("en");

                                $sql = "SELECT * FROM user_main WHERE id=" . $user->id;

                                $res = $db->sql_query($sql);
                                $ob = $db->sql_fetch_object($res);



                                $sql = "SELECT * FROM user_main WHERE id=" . $_POST['mailbox_main']['id_user_main__to'];

                                $res = $db->sql_query($sql);
                                $ob2 = $db->sql_fetch_object($res);


                                //send mail here

                                $subject = "[" . SITE_NAME . "] " . html_entity_decode($data['mailbox_main']['title'], ENT_COMPAT, 'UTF-8');

                                $msg = __('Hello') . ' ' . $ob2->firstname . ' ' . $ob2->name . ',<br />'
                                        . '<br /><br />'
                                        . '<a href="' . 'http://' . $_SERVER['SERVER_NAME'] . '/en/' . 'user/profil/inbox/' . $user->id . '">' . $ob->firstname . ' ' . $ob->name . '</a> sent you a message on Estrildidae.net.'
                                        . '<br /><br />'
                                        . '<b>Objet : ' . $data['mailbox_main']['title'] . '</b>'
                                        . '<br />'
                                        . '<b>Date : ' . date("F j, Y, H:i:s") . " CET</b>"
                                        . '<br /><br /><a href="' . 'http://' . $_SERVER['SERVER_NAME'] . '/en/' . 'user/mailbox/inbox/"><b>' . __('Click here to view the message') . '</b></a> '
                                        . '<br /><br />' . __('You do not want to receive e-mails from Estrildidae member? Change notification settings for your account. Click here to report abuse.
Your use of Estrildidae is subject to the terms of use and privacy policy of Estrildidae! and the rules of the Estrildidae community.');

                                $headers = 'MIME-Version: 1.0' . "\r\n";
                                $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

                                // En-tetes additionnels
                                $headers .= 'To: ' . $ob2->firstname . ' ' . $ob2->name . ' <' . $ob2->email . '>' . "\r\n";
                                $headers .= 'From: ' . $ob->firstname . ' ' . $ob->name . ' via Estrildidae.net (no-reply)<noreply@estrildidae.net>' . "\r\n";


                                $msg = I18n::getTranslation($msg);

                                mail($ob2->email, $subject, $msg, $headers) or die("error mail");


                                //end mail

                                I18n::SetDefault("en");

                                $msg = I18n::getTranslation(__("Your message has been sent."));
                                $title = I18n::getTranslation(__("Success"));

                                set_flash("success", $title, $msg);

                                header("location: " . LINK . "user/mailbox/inbox/");
                                exit;
                            } else {
                                die("Problem insertion boite 2");
                            }
                        } else {
                            die("Problem insertion boite 1");
                        }
                    }
                }

                $this->javascript = array("jquery.1.3.2.js", "jquery.autocomplete.min.js");
                $this->code_javascript[] = '$("#mailbox_main-id_user_main__to-auto").autocomplete("' . LINK . 'user/user_main/", {
					
					mustMatch: true,
					autoFill: false,
					max: 100,
					scrollHeight: 302,
					delay:1
					});
					$("#mailbox_main-id_user_main__to-auto").result(function(event, data, formatted) {
						if (data)
							$("#mailbox_main-id_user_main__to").val(data[1]);
					});


					';
                break;

            case 'inbox':

                $sql = "SELECT a.id,a.title,a.date,id_mailbox_etat,
					b.id as to_id, b.firstname as to_firstname, b.name as to_name, x.iso as to_iso,
					c.id as from_id, c.firstname as from_firstname, c.name as from_name, y.iso as from_iso
					FROM mailbox_main a
					INNER JOIN user_main b ON a.id_user_main__to = b.id
					INNER JOIN geolocalisation_country x on b.id_geolocalisation_country = x.id
					INNER JOIN user_main c ON a.id_user_main__from = c.id
					INNER JOIN geolocalisation_country y on c.id_geolocalisation_country = y.id
					
						WHERE id_user_main__box = '" . $user->id . "'
						AND id_user_main__to = '" . $user->id . "'
							AND id_history_etat = 1
							ORDER BY date DESC";
                $res = $db->sql_query($sql);
                $this->data['mail'] = $db->sql_to_array($res);



                break;

            case 'sent_mail':

                $sql = "SELECT a.id,a.title,a.date,id_mailbox_etat,
					b.id as to_id, b.firstname as to_firstname, b.name as to_name, x.iso as to_iso,
					c.id as from_id, c.firstname as from_firstname, c.name as from_name, y.iso as from_iso
					FROM mailbox_main a
					INNER JOIN user_main b ON a.id_user_main__to = b.id
					INNER JOIN geolocalisation_country x on b.id_geolocalisation_country = x.id
					INNER JOIN user_main c ON a.id_user_main__from = c.id
					INNER JOIN geolocalisation_country y on c.id_geolocalisation_country = y.id
						WHERE id_user_main__box = '" . $user->id . "'
						AND id_user_main__from = '" . $user->id . "'
							AND id_history_etat = 1
							ORDER BY date DESC";
                $res = $db->sql_query($sql);
                $this->data['mail'] = $db->sql_to_array($res);



                break;


            case 'all_mails':

                $sql = "SELECT a.id,a.title,a.date,id_mailbox_etat,
					b.id as to_id, b.firstname as to_firstname, b.name as to_name, x.iso as to_iso,
					c.id as from_id, c.firstname as from_firstname, c.name as from_name, y.iso as from_iso
					FROM mailbox_main a
					INNER JOIN user_main b ON a.id_user_main__to = b.id
					INNER JOIN geolocalisation_country x on b.id_geolocalisation_country = x.id
					INNER JOIN user_main c ON a.id_user_main__from = c.id
					INNER JOIN geolocalisation_country y on c.id_geolocalisation_country = y.id
					
						WHERE id_user_main__box = '" . $user->id . "'
							AND id_history_etat = 1
							ORDER BY date DESC";
                $res = $db->sql_query($sql);
                $this->data['mail'] = $db->sql_to_array($res);



                break;


            case 'trash':

                $sql = "SELECT a.id,a.title,a.date,id_mailbox_etat,
					b.id as to_id, b.firstname as to_firstname, b.name as to_name, x.iso as to_iso,
					c.id as from_id, c.firstname as from_firstname, c.name as from_name, y.iso as from_iso
					FROM mailbox_main a
					INNER JOIN user_main b ON a.id_user_main__to = b.id
					INNER JOIN geolocalisation_country x on b.id_geolocalisation_country = x.id
					INNER JOIN user_main c ON a.id_user_main__from = c.id
					INNER JOIN geolocalisation_country y on c.id_geolocalisation_country = y.id
						WHERE id_user_main__box = '" . $user->id . "'
							AND id_history_etat = 3
							ORDER BY date DESC";
                $res = $db->sql_query($sql);
                $this->data['mail'] = $db->sql_to_array($res);



                break;


            case 'msg':
                $sql = "SELECT a.id,a.title,a.date,a.text as msg,id_mailbox_etat,id_user_main__from,id_user_main__to,
					b.id as to_id, b.firstname as to_firstname, b.name as to_name, x.iso as to_iso,
					c.id as from_id, c.firstname as from_firstname, c.name as from_name, y.iso as from_iso
					FROM mailbox_main a
					INNER JOIN user_main b ON a.id_user_main__to = b.id
					INNER JOIN geolocalisation_country x on b.id_geolocalisation_country = x.id
					INNER JOIN user_main c ON a.id_user_main__from = c.id
					INNER JOIN geolocalisation_country y on c.id_geolocalisation_country = y.id
					
						WHERE a.id = '" . $db->sql_real_escape_string($param[1]) . "' 
						AND id_user_main__box = '" . $user->id . "'
							
							AND id_history_etat = 1
							ORDER BY date DESC";
                $res = $db->sql_query($sql);
                $this->data['mail'] = $db->sql_to_array($res);


                if ($this->data['mail'][0]['id_mailbox_etat'] == 2 && $user->id != $this->data['mail'][0]['id_user_main__from']) {
                    $sql = "UPDATE mailbox_main SET id_mailbox_etat = 1, `read`=now()
						WHERE id_user_main__from = '" . $this->data['mail'][0]['id_user_main__from'] . "'
						AND id_user_main__to = '" . $this->data['mail'][0]['id_user_main__to'] . "'
						AND date = '" . $this->data['mail'][0]['date'] . "'";

                    $db->sql_query($sql);
                }





                break;


            case 'delete':

                $del = array();

                /*
                  foreach ()
                  {

                  }
                  $sql = "
                 */
                break;
        }




        $this->set("data", $this->data);
    }
    
    public function install()
    {
        
        $db = $this->di['db']->sql(DB_DEFAULT);
        
        $sql = "
        CREATE TABLE IF NOT EXISTS `mailbox_main` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `id_user_main__box` int(11) NOT NULL,
        `id_user_main__from` int(11) NOT NULL,
        `id_user_main__to` int(11) NOT NULL,
        `id_history_etat` int(11) NOT NULL,
        `date` datetime NOT NULL,
        `title` varchar(100) NOT NULL,
        `text` text NOT NULL,
        `id_mailbox_etat` int(11) NOT NULL,
        `read` datetime NOT NULL,
        PRIMARY KEY (`id`)
      ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0";
        
        $db->sql_query($sql);
        
        

    }
    
    public function unInstall()
    {
        $sql = "DROP TABLE IF  EXISTS `mailbox_main`;";
        $db = $this->di['db']->sql(DB_DEFAULT);
        $db->sql_query($sql);

    }
    

}
