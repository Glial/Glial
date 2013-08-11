<?php

namespace glial;


class User {

	static function getUserNotConfirmed() {
		$_SQL = \Singleton::getInstance(SQL_DRIVER);

		$sql = "SELECT * FROM user_main WHERE (id_group = 1 and key_auth != '') OR id =3";

		$res = $_SQL->sql_query($sql);

		while ($ob = $_SQL->sql_fetch_object($res)) {
			$url = "http://www.estrildidae.net/user/confirmation/" . $ob->email . "/" . $ob->key_auth . "/";

			echo $url . "\n";

			$subject = __("Confirm your registration on www.estrildidae.net");

			$msg = __('Hello') . ' ' . $ob->firstname . ' ' . $ob->name . ' !<br />
				' . __('Thank you for registering on estrildidae.net.') . '<br />
				<br />
				' . __("To finalise your registration, please click on the confirmation link below. Once you've done this, your registration will be complete.") . '<br />
				' . __('Please') . ' <a href="' . $url . '"> ' . __('click here') . '</a> ' . __('to confirm your registration
				or copy and paste the following URL into your browser:') . '
				' . $url . '<br />
                <br />
				' . __('Many thanks');


			$msg = $GLOBALS['_LG']->getTranslation($msg);

			$headers = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";

			// En-tetes additionnels
			$headers .= 'To: ' . $ob->firstname . ' ' . $ob->name . ' <' . $ob->email . '>' . "\r\n";
			$headers .= 'From: Aurélien LEQUOY <aurelien.lequoy@gmail.com>' . "\r\n";
			//$headers .= 'Cc: anniversaire_archive@example.com' . "\r\n";
			//$headers .= 'Bcc: anniversaire_verif@example.com' . "\r\n";

			mail($ob->email, $subject, $msg, $headers) or die("error mail");
		}
	}

}


echo "namespace : ".__NAMESPACE__."\n";