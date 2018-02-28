CryptClass
==========

CryptClass is a set of Helper classes (Crypt / CryptAesClass / CryptClass) that 
greatly simplifies the job of encrypting and decrypting data including strings, 
arrays, bool and objects allowing you to get on with developing cool stuff.

Usage Examples
--------------

### Using the Crypt static class, defining your key upfront

    include_once('CryptClass.php');
    Crypt::$key = 'hello world';
    $encrypted = Crypt::encrypt($my_data);
    $decrypted = Crypt::decrypt($encrypted);

### Using the Crypt static class, defining your key at call time

    include_once('CryptClass.php');
    $encrypted = Crypt::encrypt($my_data,'hello world');
    $decrypted = Crypt::decrypt($encrypted,'hello world');

### Using CryptAesClass as a regular class

    include_once('CryptClass.php');
    $Crypt = new CryptAesClass('hello world');
    $encrypted = $Crypt->encrypt($my_data);
    $decrypted = $Crypt->decrypt($encrypted);

### Usage within the CakePHP web framework

Copy or symlink the CryptClass.php file into the Vendor path, then

in bootstrap.php or core.php

    App::uses('CryptClass', 'Vendor');
    Crypt::$key = Config::read('Security.salt');

in your application code

    $encrypted = Crypt::encrypt($my_data);
    $decrypted = Crypt::decrypt($encrypted);

Option Parameters
-----------------
The option parameters are deliberately kept simple, as per below

    $options = array(
        'compress' => true,         // compress the data before encrypting
        'base64_encode' => true,    // base64_encode the encrypted data
        'url_safe' => true,         // make the encrypted data url_safe
        'use_keygen' => true,       // transform a user supplied key into a 
                                       key using more of the available keyspace
        'keygen_length' => 32,      // where 32 = AES256, 24 = AES192, 16 = AES128
        'test_decrypt_before_return' => false,
    );

You can apply these options when you instantiate the class using the second 
parameter, like this:-
 
    $Crypt = new CryptAesClass($key,$options);

Or if using the static class approach, you set them like this:-

    Crypt::$compress = true;
    Crypt::$url_safe = true;
    Crypt::$base64_encode = true;
    Crypt::$test_decrypt_before_return = false;

Questions and Answers
=====================

Q: What about the mcrypt libraries in PHP?  
A: The mcrypt libraries are fantastic, and these classes use mcrypt!  The thing
   about mcrypt though is that the developer actually needs to learn something 
   about crypto to use it effectively without making mistakes and even then it's
   possible to unknowingly overlook something.

Q: What does the encrypted data look like?  
A: A URL safe plain string.

Q: Why would I care for encrypted data as a URL safe string again?  
A: By default the encrypted data is URL safe base64 encoded which means you can
   pass an array/object (anything) back as a part of an off-site callback, which
   was the original motivation for wanting this functionality in the first place.
   For example, say you want some kind of trusted/secure information coming back 
   to you in a Paypal return URL, that's no problem with a return URL that looks 
   something like this http://hostname/path/<enc_data>
   You just grab the <enc_data> string and decrypt it to obtain whatever data you
   were working with in the first place - makes this a great way to manage state
   when passing back and forth between off-site services with callbacks, Amazon
   S3 uploads also come to mind here.

Q: What's all this business about key length?  
A: You *MUST* use a key length of 128 or 192 or 256 bits, this is part of the way
   that AES (ie Rijndael) works.

Q: What's keygen() all about?  
A: Because 256 bits = 32 bytes * 8 bits per byte a naive way to "convert" clear 
   text passwords into 256 bits of key material is to just md5() the clear text.
   ie $key = md5('my cool password') - the result will always be a 32 character 
   string which is what you are looking for if you want to use AES256.... however 
   what you are actually doing is reducing the keyspace to use because md5() will 
   only provide you with a string of characters where each character is between 
   0 and f.  The available keyspace can be any character (including non printable) 
   ones.  Choosing a key within such limited keyspace GREATLY reduces the 
   effectiveness of AES256 so Crypt::keygen() addresses this problem by 
   transforming the user supplied key into a key that uses a much wider range 
   (but not quite all) the possible keyspace, you can change this by setting the
   'use_keygen' option to false.

Q: Do you compress the data before you encrypt it?  
A: By default yes, though you can disable this with an option if you want.

Q: What encryption does it use, can it be broken?  
A: AES128, AES192, AES256 depending on the key length the developer uses for 
   encryption - AES is the US data encryption standard, it is considered to be
   very resilient to attack.  By default CryptAesClass uses AES256.

Q: Your _encryptData() function says it is using something called Rijndael?  
A: Rijndael with a block size 128 bits and key bit of lengths 128, 192 or 256 are 
   the AES encryption standard.  We use AES because it is a trusted encryption
   scheme that has been peer reviewed and examined at length.

Q: Dude, AES was chosen by the US government!  
A: Yep, if that worries you... oh I'm not going to bother, good luck.

Q: What's the deal with the old CryptClass?  
A: In short, there was a glaring problem, my old CryptClass used a static IV and
   it was not as easy to use as this Class.

Q: Why is CryptAesClass better than the old CryptClass?  
A: Other than addressing the IV issue this version is simpler to use and comes
   with a test harness that 100% passes - I discovered after testing there was a 
   strange edge case where the padded null bytes that are introduced through the 
   encrypt process would sometimes cause gzuncompress to fail, ouch!  Also this 
   version is binary safe due to the use of PHP serialize() rather than json.

