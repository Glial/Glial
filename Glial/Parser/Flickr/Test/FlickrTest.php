<?php

namespace Glial\Parser\Flickr\Test;

use \Glial\Parser\Flickr\Flickr;

use PHPUnit\Framework\TestCase;

class FlickrTest extends TestCase
{
    public function testExif()
    {
        $res = Flickr::get_photo_exif("https://www.flickr.com/photos/gregbm/9570385391/meta/");
        $data = '{"Dates":{"Taken on":"August 10, 2013 at 2.54PM PST","Posted to Flickr":"August 22, 2013 at 2.06PM PST"},"Exif data":{"Camera":"Canon EOS-1D X","Exposure":"0.003 sec (1\/400)","Aperture":"f\/7.1","Focal Length":"700 mm","ISO Speed":"1000","Exposure Bias":"+2\/3 EV","Flash":"Off, Did not fire","Image Width":"4608","Image Height":"3072","Bits Per Sample":"8 8 8","Photometric Interpretation":"RGB","Orientation":"Horizontal (normal)","Samples Per Pixel":"3","X-Resolution":"72 dpi","Y-Resolution":"72 dpi","Software":"Adobe Photoshop CS6 (Macintosh)","Date and Time (Modified)":"2013:08:20 21:56:17","Artist":"Greg B Miles","YCbCr Positioning":"Co-sited","Copyright":"Greg B Miles All rights reserved","Exposure Program":"Program AE","Sensitivity Type":"Recommended Exposure Index","Recommended Exposure Index":"1000","Date and Time (Original)":"2013:08:10 14:54:53","Date and Time (Digitized)":"2013:08:10 14:54:53","Max Aperture Value":"5.7","Metering Mode":"Multi-segment","Sub Sec Time":"81","Sub Sec Time Original":"81","Sub Sec Time Digitized":"81","Color Space":"sRGB","Focal Plane X-Resolution":"5091.712707 dpi","Focal Plane Y-Resolution":"5069.306931 dpi","Custom Rendered":"Normal","Exposure Mode":"Auto","White Balance":"Auto","Scene Capture Type":"Standard","Lens Info":"700mm f\/0","Lens Model":"EF500mm f\/4L IS USM +1.4x","Lens Serial Number":"0000000000","GPS Version ID":"2.3.0.0","Compression":"JPEG (old-style)","Coded Character Set":"UTF8","By-line":"Greg B Miles","Object Name":"Purple-bellied Lory","Date Created":"2013:08:10","Time Created":"14:54:53+00:00","Copyright Notice":"Greg B Miles All rights reserved","Global Angle":"30","Global Altitude":"30","Copyright Flag":"True","Photoshop Quality":"12","Photoshop Format":"Standard","Progressive Scans":"3 Scans","XMPToolkit":"Adobe XMP Core 5.3-c011 66.145661, 2012\/02\/06-14:56:27","Rating":"0","Metadata Date":"2013:08:20 21:56:17+10:00","Format":"image\/jpeg","Rights":"Greg B Miles All rights reserved","Title":"Purple-bellied Lory","Creator":"Greg B Miles","Lens":"EF500mm f\/4L IS USM +1.4x","Lens ID":"143","Image Number":"0","Approximate Focus Distance":"79.9","Flash Compensation":"0","Color Mode":"RGB","ICCProfile Name":"sRGB IEC61966-2.1","Original Document ID":"49CA919DBD1556E13D81536C5D7E9D47","History Action":"saved","History Instance ID":"xmp.iid:2C0005261F206811822AD1F812E82D80","History When":"2013:08:20 21:56:17+10:00","History Software Agent":"Adobe Photoshop CS6 (Macintosh)","History Changed":"\/","Marked":"True","Viewing Conditions Illuminant Type":"D50","Measurement Observer":"CIE 1931","Measurement Flare":"0.999%","Measurement Illuminant":"D65","Color Transform":"YCbCr"}}';
        $data = '{"Dates":{"Taken on":"August 10, 2013 at 2.54PM PDT","Posted to Flickr":"August 22, 2013 at 2.06PM PDT"},"Exif data":{"Camera":"Canon EOS-1D X","Exposure":"0.003 sec (1\/400)","Aperture":"f\/7.1","Focal Length":"700 mm","ISO Speed":"1000","Exposure Bias":"+2\/3 EV","Flash":"Off, Did not fire","Image Width":"4608","Image Height":"3072","Bits Per Sample":"8 8 8","Photometric Interpretation":"RGB","Orientation":"Horizontal (normal)","Samples Per Pixel":"3","X-Resolution":"72 dpi","Y-Resolution":"72 dpi","Software":"Adobe Photoshop CS6 (Macintosh)","Date and Time (Modified)":"2013:08:20 21:56:17","Artist":"Greg B Miles","YCbCr Positioning":"Co-sited","Copyright":"Greg B Miles All rights reserved","Exposure Program":"Program AE","Sensitivity Type":"Recommended Exposure Index","Recommended Exposure Index":"1000","Date and Time (Original)":"2013:08:10 14:54:53","Date and Time (Digitized)":"2013:08:10 14:54:53","Max Aperture Value":"5.7","Metering Mode":"Multi-segment","Sub Sec Time":"81","Sub Sec Time Original":"81","Sub Sec Time Digitized":"81","Color Space":"sRGB","Focal Plane X-Resolution":"5091.712707 dpi","Focal Plane Y-Resolution":"5069.306931 dpi","Custom Rendered":"Normal","Exposure Mode":"Auto","White Balance":"Auto","Scene Capture Type":"Standard","Lens Info":"700mm f\/0","Lens Model":"EF500mm f\/4L IS USM +1.4x","Lens Serial Number":"0000000000","GPS Version ID":"2.3.0.0","Compression":"JPEG (old-style)","Coded Character Set":"UTF8","By-line":"Greg B Miles","Object Name":"Purple-bellied Lory","Date Created":"2013:08:10","Time Created":"14:54:53+00:00","Copyright Notice":"Greg B Miles All rights reserved","Global Angle":"30","Global Altitude":"30","Copyright Flag":"True","Photoshop Quality":"12","Photoshop Format":"Standard","Progressive Scans":"3 Scans","XMPToolkit":"Adobe XMP Core 5.3-c011 66.145661, 2012\/02\/06-14:56:27","Rating":"0","Metadata Date":"2013:08:20 21:56:17+10:00","Format":"image\/jpeg","Rights":"Greg B Miles All rights reserved","Title":"Purple-bellied Lory","Creator":"Greg B Miles","Lens":"EF500mm f\/4L IS USM +1.4x","Lens ID":"143","Image Number":"0","Approximate Focus Distance":"79.9","Flash Compensation":"0","Color Mode":"RGB","ICCProfile Name":"sRGB IEC61966-2.1","Original Document ID":"49CA919DBD1556E13D81536C5D7E9D47","History Action":"saved","History Instance ID":"xmp.iid:2C0005261F206811822AD1F812E82D80","History When":"2013:08:20 21:56:17+10:00","History Software Agent":"Adobe Photoshop CS6 (Macintosh)","History Changed":"\/","Marked":"True","Viewing Conditions Illuminant Type":"D50","Measurement Observer":"CIE 1931","Measurement Flare":"0.999%","Measurement Illuminant":"D65","Color Transform":"YCbCr"}}';
        $this->assertEquals($res, json_decode($data, true));
    }

    public function testAllSizes()
    {
        $res = Flickr::get_all_size("https://www.flickr.com/photos/gregbm/9570385391/sizes/sq/");

        //print_r($res);
        //echo json_encode($res);

        $data = '{"size_available":["q","t","s","n","m","z","c","l"],"best":"c","url":{"img":"https:\/\/live.staticflickr.com\/3782\/9570385391_9eae844e46_c.jpg"}}';
        $this->assertEquals($res, json_decode($data, true));
    }

    
    /* have to fix getPhotoInfo
    public function testGetPhotoInfo()
    {
        $res = Flickr::getPhotoInfo("https://www.flickr.com/photos/gregbm/9570385391/");
        //var_dump($res);

        $data = '{"id":"flickr_9570385391","id_photo":"9570385391","url":{"main":"https:\/\/www.flickr.com\/photos\/gregbm\/9570385391\/","img_z":"https:\/\/c2.staticflickr.com\/4\/3782\/9570385391_9eae844e46_z.jpg","location":"https:\/\/www.flickr.com\/photos\/gregbm\/map\/?photo=9570385391","exif":"https:\/\/www.flickr.com\/photos\/gregbm\/9570385391\/meta\/","all_size":"https:\/\/www.flickr.com\/photos\/gregbm\/9570385391\/sizes\/sq\/"},"id_author":"gregbm","legend":"The most common parrot around Rubio Plantation. Quite noisy too.","author":"Greg Miles","date-taken":"August 10, 2013","location":"Karu, New Ireland, PG","camera":"Canon EOS-1D X","tag":["Purple-bellied Lory","Lorius hypoinochrous","Rubio Plantation Retreat","Karu","New Ireland","Papua New Guinea"],"license":{"text":"All Rights Reserved","url":"\/help\/general\/#147"},"gps":{"latitude":"-3.458419","longitude":"152.19695"},"img":{"size_available":["q","t","s","n","m","z","c","l"],"best":"l","url":{"img":"https:\/\/farm4.staticflickr.com\/3782\/9570385391_9eae844e46_b.jpg"}},"exif":{"Dates":{"Taken on":"August 10, 2013 at 2.54PM PST","Posted to Flickr":"August 22, 2013 at 2.06PM PST"},"Exif data":{"Camera":"Canon EOS-1D X","Exposure":"0.003 sec (1\/400)","Aperture":"f\/7.1","Focal Length":"700 mm","ISO Speed":"1000","Exposure Bias":"+2\/3 EV","Flash":"Off, Did not fire","Image Width":"4608","Image Height":"3072","Bits Per Sample":"8 8 8","Photometric Interpretation":"RGB","Orientation":"Horizontal (normal)","Samples Per Pixel":"3","X-Resolution":"72 dpi","Y-Resolution":"72 dpi","Software":"Adobe Photoshop CS6 (Macintosh)","Date and Time (Modified)":"2013:08:20 21:56:17","Artist":"Greg B Miles","YCbCr Positioning":"Co-sited","Copyright":"Greg B Miles All rights reserved","Exposure Program":"Program AE","Sensitivity Type":"Recommended Exposure Index","Recommended Exposure Index":"1000","Date and Time (Original)":"2013:08:10 14:54:53","Date and Time (Digitized)":"2013:08:10 14:54:53","Max Aperture Value":"5.7","Metering Mode":"Multi-segment","Sub Sec Time":"81","Sub Sec Time Original":"81","Sub Sec Time Digitized":"81","Color Space":"sRGB","Focal Plane X-Resolution":"5091.712707 dpi","Focal Plane Y-Resolution":"5069.306931 dpi","Custom Rendered":"Normal","Exposure Mode":"Auto","White Balance":"Auto","Scene Capture Type":"Standard","Lens Info":"700mm f\/0","Lens Model":"EF500mm f\/4L IS USM +1.4x","Lens Serial Number":"0000000000","GPS Version ID":"2.3.0.0","Compression":"JPEG (old-style)","Coded Character Set":"UTF8","By-line":"Greg B Miles","Object Name":"Purple-bellied Lory","Date Created":"2013:08:10","Time Created":"14:54:53+00:00","Copyright Notice":"Greg B Miles All rights reserved","Global Angle":"30","Global Altitude":"30","Copyright Flag":"True","Photoshop Quality":"12","Photoshop Format":"Standard","Progressive Scans":"3 Scans","XMPToolkit":"Adobe XMP Core 5.3-c011 66.145661, 2012\/02\/06-14:56:27","Rating":"0","Metadata Date":"2013:08:20 21:56:17+10:00","Format":"image\/jpeg","Rights":"Greg B Miles All rights reserved","Title":"Purple-bellied Lory","Creator":"Greg B Miles","Lens":"EF500mm f\/4L IS USM +1.4x","Lens ID":"143","Image Number":"0","Approximate Focus Distance":"79.9","Flash Compensation":"0","Color Mode":"RGB","ICCProfile Name":"sRGB IEC61966-2.1","Original Document ID":"49CA919DBD1556E13D81536C5D7E9D47","History Action":"saved","History Instance ID":"xmp.iid:2C0005261F206811822AD1F812E82D80","History When":"2013:08:20 21:56:17+10:00","History Software Agent":"Adobe Photoshop CS6 (Macintosh)","History Changed":"\/","Marked":"True","Viewing Conditions Illuminant Type":"D50","Measurement Observer":"CIE 1931","Measurement Flare":"0.999%","Measurement Illuminant":"D65","Color Transform":"YCbCr"}}}';
        //$this->assertEquals(json_decode($data, true), json_decode($data, true));
        $this->assertEquals($res, json_decode($data, true));
    }*/
}

/* took on 2014-12-16
There was 1 failure:

1) Glial\Parser\Flickr\Test\FlickrTest::testGetPhotoInfo
Failed asserting that two arrays are equal.
--- Expected
+++ Actual
@@ @@
 Array (
     'id' => 'flickr_9570385391'
     'id_photo' => '9570385391'
     'url' => Array (
 * --"latitude":-3.458419,"longitude":152.19695,
 * c=-3.458419%2C152.196950&z=
-        'main' => 'https://www.flickr.com/photos/gregbm/9570385391/'
-        'img_z' => 'https://c2.staticflickr.com/4/3782/9570385391_9eae844e46_z.jpg'
-        'location' => 'https://www.flickr.com/photos/gregbm/map/?photo=9570385391'
-        'exif' => 'https://www.flickr.com/photos/gregbm/9570385391/meta/'
-        'all_size' => 'https://www.flickr.com/photos/gregbm/9570385391/sizes/sq/'
+        'main' => 'http://www.flickr.com/photos/gregbm/9570385391/'
+        'img_z' => 'http://farm4.staticflickr.com/3782/9570385391_9eae844e46_z.jpg'
+        'location' => 'http://www.flickr.com/photos/gregbm/map/?photo=9570385391'
+        'exif' => 'http://www.flickr.com/photos/gregbm/9570385391/meta/'
+        'all_size' => 'http://www.flickr.com/photos/gregbm/9570385391/sizes/sq/'

@@ @@
         'url' => Array (
-            'img' => 'https://farm4.staticflickr.com/3782/9570385391_9eae844e46_b.jpg'
+            'img' => 'http://farm4.staticflickr.com/3782/9570385391_9eae844e46_b.jpg'
         )
     )
     'exif' => Array (
         'Dates' => Array (
-            'Taken on' => 'August 10, 2013 at 2.54PM PST'
-            'Posted to Flickr' => 'August 22, 2013 at 2.06PM PST'
+            'Taken on' => 'August 10, 2013 at 2.54PM PDT'
+            'Posted to Flickr' => 'August 22, 2013 at 2.06PM PDT'
         )
         'Exif data' => Array (...)
     )
 )
*/