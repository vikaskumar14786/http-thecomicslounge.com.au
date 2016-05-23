<?php
/*
* phpPicasa get datas of galleries, pictures from Picasaweb, titles, captions, user name
*
* @id $Id$
* @author  GraphicAholic.com (c) 2013
* @license  GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
*/
// no direct access

class phpPicasahelper {

    private $url=null;          //Google AtomFeed URL
    private $parsed=array();    //an unprocessed array from AtomFeed XML
    private $result=array();    //an associative array from parsed array
    private $userid=null;       //Picasaweb username
    private $albumid=null;      //album id */
    /* private $albumid=null;      //album id */
    private $thumbsize=null;    //thumbnail size for album lists or photo lists
    private $picturesize=null;   //picture size for photos of an album

    /*
     * Function parse parsing Google API AtomFeed XML
     */
    private function parse() {

        $a=xml_parser_create();
        xml_parse_into_struct($a, $this->getUrlContent(), $values);
        xml_parser_free($a);

        $this->parsed=$values;
        
    }

    /*
     * Function makeArrayFull makes an array,
     * that contain all data comes from Google API AtomFeed XML
     */
    private function makeArrayFull() {
        
        $rsstomb=$this->parsed;

        $result=array();

        $level1=1;
        $level2=2;
        $level3=3;
        $level4=4;

        $level2i=1;
        $level3i=1;

        $i=0;

        $level1name=$rsstomb[$i]['tag'];
        $i++;
        
        while ($rsstomb[$i]['level']!=$level1 or $rsstomb[$i]['type']!='close') {
            $level2name=$rsstomb[$i]['tag'];
            if ($level2name==$rsstomb[$i-1]['tag']) {
                $level2name=$level2name.$level2i++;
            }
            else {
                $level2i=1;
            }
            if ($rsstomb[$i]['level']==$level2 and $rsstomb[$i]['type']=='complete') {
                if (isset($rsstomb[$i]['attributes'])) {
                    $result[$level1name][$level2name]['attributes']=$rsstomb[$i]['attributes'];
                }
                if (isset($rsstomb[$i]['value'])) {
                    $result[$level1name][$level2name]['value']=$rsstomb[$i]['value'];
                }
            }
            elseif ($rsstomb[$i]['level']==$level2 and $rsstomb[$i]['type']=='open') {
                if (isset($rsstomb[$i]['attributes'])) {
                    $result[$level1name][$level2name]['attributes']=$rsstomb[$i]['attributes'];
                }
                if (isset($rsstomb[$i]['value'])) {
                    $result[$level1name][$level2name]['value']=$rsstomb[$i]['value'];
                }
                $i++;
                while ($rsstomb[$i]['level']!=$level2 or $rsstomb[$i]['type']!='close') {
                    $level3name=$rsstomb[$i]['tag'];
                    if ($level3name==$rsstomb[$i-1]['tag']) {
                        $level3name=$level3name.$level3i++;
                    }
                    else {
                        $level3i=1;
                    }
                    if ($rsstomb[$i]['level']==$level3 and $rsstomb[$i]['type']=='complete') {
                        if (isset($rsstomb[$i]['attributes'])) {
                            $result[$level1name][$level2name][$level3name]['attributes']=$rsstomb[$i]['attributes'];
                        }
                        if (isset($rsstomb[$i]['value'])) {
                            $result[$level1name][$level2name][$level3name]['value']=$rsstomb[$i]['value'];
                        }
                    }
                    elseif ($rsstomb[$i]['level']==$level3 and $rsstomb[$i]['type']=='open') {
                        if (isset($rsstomb[$i]['attributes'])) {
                            $result[$level1name][$level2name][$level3name]['attributes']=$rsstomb[$i]['attributes'];
                        }
                        if (isset($rsstomb[$i]['value'])) {
                            $result[$level1name][$level2name][$level3name]['value']=$rsstomb[$i]['value'];
                        }
                        $i++;
                        while ($rsstomb[$i]['level']!=$level3 or $rsstomb[$i]['type']!='close') {
                            $level4name=$rsstomb[$i]['tag'];
                            if ($rsstomb[$i]['level']==$level4 and $rsstomb[$i]['type']=='complete') {
                                if (isset($rsstomb[$i]['attributes'])) {
                                    $result[$level1name][$level2name][$level3name][$level4name]['attributes']=$rsstomb[$i]['attributes'];
                                }
                                if (isset($rsstomb[$i]['value'])) {
                                    $result[$level1name][$level2name][$level3name][$level4name]['value']=$rsstomb[$i]['value'];
                                }
                            }
                            $i++;
                        }
                    }
                    $i++;
                }
            }
            $i++;
        }

        $this->result=$result;

    }

    /*
     * Function getUrlContent gets content of Google API AtomFeed URL
     */
    private function getUrlContent()
	{
        if(empty($this->url))
            {
            throw new Exception("URL to parse is empty!.");
            return false;
            }

        if($content = @file_get_contents($this->url))
            {
            return $content;
            }
        else
            {
            $ch=curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $content=curl_exec($ch);
            $error=curl_error($ch);

            curl_close($ch);

            if(empty($error))
                {
                return $content;
                }
            else
                {
                throw new Exception("Erroe occured while loading url by cURL. <br />\n" . $error) ;
                return false;
                }
            }

        }

        /*
         * Function makeURL makes Google AtomFeed URL
         * from username, albumid, and optional thumbnail size
         */
        private function makeURL() {

            if ($this->thumbsize!="") {
                $thumbsize="&thumbsize=" . $this->thumbsize;
            }
            else {
                $thumbsize="";
            }
            if ($this->picturesize!="") {
                $picturesize="&imgmax=" . $this->picturesize;
            }
            else {
                $picturesize="";
            }
            $ret="http://picasaweb.google.com/data/feed/api";
            if ($this->userid!="") {
                $ret.="/user/" . $this->userid;
            }
            if ($this->albumid!="") {
                $ret.="/albumid/" . $this->albumid . "?kind=photo&access=public" . $thumbsize . $picturesize;
            }
            else {
                $ret.="?kind=album&access=public" . $thumbsize;
            }
        return $ret;
        }

        /*
         * Function fromURLtoArray calls 3 different function
         */
        private function fromURLtoArray() {
            $this->url=$this->makeURL();
            $this->parse();
            $this->makeArrayFull();
        }

        /*
         * Function getKeys gets similar keys of $key from full array
         */
        private function getKeys($key) {
            $keys=array_keys($this->result["FEED"]);
            $entry=array();
            $entry_key=0;
            for ($i=0;$i<count($keys);$i++) {
                if (preg_match("/" . $key . "/", $keys[$i])) {
                    $entry[$entry_key++]=$keys[$i];
                }
            }
            return $entry;
        }

        /*
         * Function getAlbums gets some data from Picasaweb.
         *
         * getAlbums(userid,thumbsize)
         *
         * parameters:
         * userid (your picasaweb userid)
         * thumbsize (size of thumbnail images) optional, default is 160px
         * * * you can use this sizes: 32, 48, 64, 72, 104, 144, 150, 160, 176, 192, 208, 224, 240, 256, 272, 288, 304, 320, 336,
         * * * this pictures available full and crop format
         * * * for example:
         * * * * you want to use 32px crop thumbnail: thumbsize value is "32c"
         * * * * full size: "32u"
         *
         * Return in this data format:
         *
         * "albumid"=>"name"="albumname (type: string)",
         * "albumid"=>"numphotos"="number of photos (type: string)",
         * "albumid"=>"published"="publication time (type: unix timestamp)",
         * "albumid"=>"thumbnail"="URL of thumbnail image (type: string)",
         * "albumid"=>"title"="title of album (type: string)")
         * "albumid"=>"description"="description of photos (type: string)")
		 * "albumid"=>"credit"="credit of photos (type: string)")
         *
         */
        public function getAlbums($userid,$thumbsize="64c") {
            $albums=array();
            $this->userid=$userid;

            $this->thumbsize=$thumbsize;
            $this->fromURLtoArray();
			
			
	    $id=0;
            foreach ($this->getKeys("ENTRY") AS $key=>$value) {
                
                foreach ($this->result["FEED"][$value] AS $gphotokey=>$gphotovalue) {
                    switch ($gphotokey) {
                        case "GPHOTO:ID":
                            $albums[$id]['id']=$gphotovalue["value"];
                            break;
                        case "GPHOTO:NAME":
                            $albums[$id]['name']=$gphotovalue["value"];
                            break;
                        case "GPHOTO:NUMPHOTOS":
                            $albums[$id]['numphotos']=$gphotovalue["value"];
                            break;
                        case "GPHOTO:TIMESTAMP":
                            $albums[$id]['published']=substr($gphotovalue["value"],0,10);
                            break;
							
                        case "MEDIA:GROUP":
                            foreach ($gphotovalue AS $mediakey=>$mediavalue) {
                                if ($mediakey=="MEDIA:THUMBNAIL") {
                                    $albums[$id]['thumbnail']=$mediavalue["attributes"]["URL"];
                                }
                                if ($mediakey=="MEDIA:TITLE") {
                                    $albums[$id]['title']=$mediavalue["value"];
                                }
								if ($mediakey=="MEDIA:DESCRIPTION") {
								$albums[$id]['caption']=$mediavalue["value"];
								}
                                if ($mediakey=="MEDIA:CREDIT") {
                                    $albums[$id]['credit']=$mediavalue["value"];
                                }
                            }
                            break;
                        default:
                            break;
                    }
                }
	    $id++;
            }
            return $albums;
        }

        /*
         * Function getPictures gets some data from Picasaweb.
         *
         * getPictures(userid,albumid,thumbsize)
         *
         * parameters:
         * userid (your picasaweb userid)
         * albumid (id of album)
         * thumbsize (size of thumbnail images) optional, default is 160px
         * * * you can use this sizes: 32, 48, 64, 72, 104, 144, 150, 160
         * * * this pictures available full and crop format
         * * * for example you want to use 32px crop thumbnail: thumbsize value is "32c"
         * * * full size: "32u"
         * picturesize (size of pictures) otional, default is 512px
         * * * you can use this sizes: 94, 110, 128, 200, 220, 288, 
         * * * * 320, 400, 512, 576, 640, 720, 800, 912, 1024, 1152, 1280, 1440, 1600
         *
         * Return in this data format:
         *
         * "albumtitle"="name of album (type: string)",
         * "pictureid"=>"published"="publication time (type: unix timestamp)",
         * "pictureid"=>"picture"="URL of picture image (type: string)",
         * "pictureid"=>"thumbnail"="URL of thumbnail image (type: string)",
         * "pictureid"=>"title"="title of album (type: string)")
         *
         */
        public function getPictures($userid,$albumid,$caption,$thumbsize="64c",$picturesize="") {

            $pictures=array();

            $this->userid=$userid;
            $this->albumid=$albumid;
            $this->thumbsize=$thumbsize;
            $this->picturesize=$picturesize;
            $this->description=$description;
            $this->credit=$credit;
            $this->fromURLtoArray();

            $pictures['albumtitle']=$this->result["FEED"]["TITLE"]["DESCRIPTION"]["CREDIT"]["value"];
	    $id=0;
            foreach ($this->getKeys("ENTRY") AS $key=>$value) {
                foreach ($this->result["FEED"][$value] AS $gphotokey=>$gphotovalue) {
                    switch ($gphotokey) {
                        case "GPHOTO:ID":
                            $pictures[$id]['id']=$gphotovalue["value"];
                            break;
                        case "GPHOTO:TIMESTAMP":
                            $pictures[$id]['published']=substr($gphotovalue["value"],0,10);
                            break;
                        case "MEDIA:GROUP":
                            foreach ($gphotovalue AS $mediakey=>$mediavalue) {
                                if ($mediakey=="MEDIA:CONTENT") {
                                    $pictures[$id]['picture']=$mediavalue["attributes"]["URL"];
                                }
                                if ($mediakey=="MEDIA:THUMBNAIL") {
                                    $pictures[$id]['thumbnail']=$mediavalue["attributes"]["URL"];
                                }
                                if ($mediakey=="MEDIA:TITLE") {
                                    $pictures[$id]['title']=$mediavalue["value"];
                                }
						if ($mediakey=="MEDIA:DESCRIPTION") {
						$pictures[$id]['caption']=$mediavalue["value"];
						}
                                if ($mediakey=="MEDIA:CREDIT") {
                                    $pictures[$id]['credit']=$mediavalue["value"];
                                }
                            }
                            break;
                        default:
                            break;
                    }
                }
	    $id++;
            }
            return $pictures;
        }

}

?>
