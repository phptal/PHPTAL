<?php header('Content-type: text/html; charset=utf-8'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
  <head>
    <meta name="http-equiv" content="Content-type: text/html; charset=utf-8"/>
    <title>phptal :: files</title>
    <style type="text/css">

    body { font-family: Helvetica, sans-serif; }
    h1 { margin-bottom: 0px; padding-bottom: 0px; }
    a { color: #990000; text-decoration: none; }
    a:hover { background-color: #FFFF66; }

    div.fileList {
        padding: 2px;
        border: 1px dashed #333;
        float: left;
        clear: both;
    }
    
    div.browse { 
        margin-top: 5px;
        margin-bottom: 5px;
        min-height: 48px; 
        min-width: 300px;
        padding-left: 50px;
        padding-right: 5px;
        padding-top: 2px;
        font-size: 12px;
        font-family: Arial, Helvetica, sans-serif;
        background-image: url('http://yota.dyndns.org/gfx/txt.png');
        background-repeat: no-repeat;
        font-style: overline;
        cursor: pointer;
        float: left;
        clear: both;
    }

    div.browse h3 {
        padding: 0px;
        margin: 0px;
        font-weight: bold;
        font-size: 12px;
    }
    div.browse span.date {
        display: block;
        color: #777;
    }
    div.browse span.size {
        display: block;
    }

    div.browse a { text-decoration: none; color: #333; }
    div.browse a:hover { text-decoration: none; color: #333; background: none; }
    div.browse:hover { background-color: #EEE; }

    div.txt { background-image: url('/gfx/txt.png'); }
    div.jpg { background-image: url('/gfx/jpg.png'); }
    div.gif { background-image: url('/gfx/gif.png'); }
    div.png { background-image: url('/gfx/png.png'); }
    div.cpp { background-image: url('/gfx/cpp.png'); }
    div.c   { background-image: url('/gfx/c.png');   }
    div.h   { background-image: url('/gfx/h.png');   }
    div.gz  { background-image: url('/gfx/gz.png');  }
    div.tgz { background-image: url('/gfx/tgz.png'); }

    </style>
    <script type="text/javascript">
    // <![CDATA[
    function download(file){
        document.location = file;
    }
    // ]]>
    </script>
  </head>
  <body>

    <h1><a href="http://phptal.motion-twin.com">PHPTAL</a> :: files</h1>

    <p>
    If this page is not well rendered by your browser, upgrade your browser or 
    <a href="http://www.mozilla.org">replace it</a>.
    </p>

    <noscript>
    <div style="margin-bottom: 1em;">
    It seems you have deactivated javascript, you will have to click on the
    download name (the file name in bold) to access files. Do not mind about this
    sentence if you are using a text browser :)
    </div>
    </noscript>
    
    <div class="fileList">
<?php 

$files = array();

$d = dir(dirname(__FILE__));
while ($f = $d->read()){
    if (is_file($f)) array_push($files, $f);
}
$d->close();

sort($files);
foreach ($files as $f){
    present_file($f);
}

function present_file($f)
{
    if ($f[0] == '.') return;
    if (is_file($f)){
        preg_match('/\.([a-z0-9]+)$/', $f, $m);
        if ($m && $m[1] != 'php'){
            show_entry($f, $m[1], date('Y-m-d H:i:s', filemtime($f)), filesize($f));
        }
    }
}

function show_entry($name, $class, $date, $size){ ?>
    <?php $htmlName = htmlentities($name); ?>
    <div class="browse <?php echo $class ?>" onclick="download('<?php echo $htmlName ?>');">
    <h3><a href="<?php echo $htmlName ?>"><?php echo $htmlName ?></a></h3>
    <span class="date"><?php echo show_date($date) ?></span>
    <span class="size"><?php echo show_size($size) ?></span>
    </div>
<?php }

function show_size($size){ 
    $ko = $size / 1024;
    $mo = $ko / 1024;
    if ($mo > 1){
        return sprintf('%.2f Mo', $mo);
    }
    if ($ko > 1){
        return sprintf('%.2f Ko', $ko);
    }
    return $size.' o';
}

function show_date($date){ return $date; }

?>
    </div>
  </body>
</html>
