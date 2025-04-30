<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<title>View non-printable unicode characters</title>
<style type="text/css">
body {
	font-family: Helvetica, Arial, sans-serif;
	background-color: #EEEEEE;
}
div.main {
	background-color: white;
	border-radius: 10px;
	border: 2px solid #666666;
	box-shadow: 5px 5px 5px #999999;
	padding: 2.4em 3em;
	margin: 0 auto;
	max-width: 1000px;
}
div.output {
    margin: 3em 0;
    border: 1px solid #666666;
    padding: 0.4em 0.2em;
	font-family: Courier New, Courier, monospaced;
	cursor: default;
}
div.output span.symbol {
	color: white;
	background-color: #999999;
	padding: 0 2px;
	margin: 0 2px;
}
div.output span.hex {
	color: black;
	background-color: #9999FF;
	padding: 0 2px;
	margin: 0 2px;
}

span.S2Tooltip.anchor {
  	white-space: nowrap;
}
span.S2Tooltip.container {
	position: absolute; z-index: 999;
  	left: -99999px; top: -99999px;
}
span.S2Tooltip.anchor:hover {
	background-color: #FF9999;
}
span.S2Tooltip.anchor:hover + span.S2Tooltip.container {
  	left: auto; top: auto;
}
span.S2Tooltip.tiptext {
  	position: absolute;
  	left: -2em; top: 1.5em; 
	padding: 0.5em 0.8em 0.7em 0.8em;
	color: black; background-color: #DDDDFF; border: 1px solid #9999FF;
	font-weight: normal; text-align: left; 
	display: block;
  	text-indent: 0;
}

</style>
</head>
<body>

<div class="main">
	<h1>View non-printable unicode characters</h1>
	<p>Online tool to display non-printable characters that may be hidden in copy&amp;pasted strings.</p>
	

<?php

/**
 * Show a represenatation for non-printable characters
 */
mb_internal_encoding('UTF-8');  // Required to always correctly use mb_encode_mimeheader() - probably alias for ini_set('mbstring.internal_encoding', 'ISO-8859-15'); (which definitely works)
mb_regex_encoding('UTF-8');  // Selection uses the multibyte replace
define('NL', "\r\n");

class ViewChars
{
	
	private static function htmlChar($c)
	{
		if (strlen($c) === 1) {
			$desc =
				ord($c).'<br>'.NL.
				'0x'.sprintf('%02s', dechex(ord($c)));
			$hex = sprintf('%02s', dechex(ord($c)));
		} else {
			$n = unpack('V', iconv('UTF-8', 'UCS-4LE', $c))[1];
			$desc =
				'&amp;#'.$n.';<br>'.NL.
				'\u'.sprintf('%04s', strtoupper(dechex($n)));
			$hex = 'U+'.strtoupper(dechex($n));
		}
		
		if ($c === "\r") {
			$symbol = '<span class="symbol S2Tooltip anchor">CR</span>';
		} elseif ($c === "\n") {
			$symbol = '<span class="symbol S2Tooltip anchor">LF</span>';
		} elseif ($c === "\t") {
			$symbol = '<span class="symbol S2Tooltip anchor">⟶</span>&#8203;';
		} elseif ($c === " ") {
			$symbol = '<span class="white S2Tooltip anchor">·</span>&#8203;';
		} else {
			if (preg_match('/^[\\p{L}\\p{M}\\p{N}\\p{P}\\p{S}]$/u', $c)) {
				$symbol = '<span class="S2Tooltip anchor">'.$c.'</span>';
			} else {
				$symbol = '<span class="hex S2Tooltip anchor">'.$hex.'</span>';
			}
		}
		
		return
			$symbol.
				'<span class="S2Tooltip container">'.
				'<span class="S2Tooltip tiptext rounded shadow">'.$desc.'</span>'.
				'</span>';
	}
	
	private static function text2html($s)
	{
	    $html = '<div class="output" dir="auto">'.NL;
	    
	    $sl = mb_strlen($s);
	    $nlc = 0;
	    for ($i=0; $i<$sl; $i++) {
	        $c = mb_substr($s, $i, 1);
	        if ($c === "\r") {
	            if ($nlc === 0) {
	                $nlc = 1;
	                $html.= self::htmlChar($c);
	            } elseif ($nlc === 1) {
	                $html.= '<br>'.NL.self::htmlChar($c);
	                $nlc = 1;
	            } elseif ($nlc === 2) {
	                $html.= self::htmlChar($c).'<br>'.NL;
	                $nlc = 0;
	            }
	        } elseif ($c === "\n") {
	            $sym = self::htmlChar($c);
	            if ($nlc === 0) {
	                $nlc = 2;
	                $html.= $sym;
	            } elseif ($nlc === 2) {
	                $html.= '<br>'.NL.self::htmlChar($c);
	                $nlc = 2;
	            } elseif ($nlc === 1) {
	                $html.= self::htmlChar($c).'<br>'.NL;
	                $nlc = 0;
	            }
	        } else {
	            $html.= self::htmlChar($c);
	        }
	        
	    }
	    
	    return $html.'</div>'.NL;
	}
	
	public static function monkey()
	{
		$s = (isset($_REQUEST['s']) ? $_REQUEST['s'] : json_decode('"See\u00A0what\'s hidden in your string\u2026\tor be\\u200Bhind\uFEFF"'));

		return
			'<form action="" method="POST" accept-charset="UTF-8">
 			<div style="margin-top: 3em;">
 				Please paste the string here:
 			</div>
 			<div>
 				<textarea name="s" rows="8" cols="40" style="width: 100%; box-sizing: border-box;" dir="auto">'.htmlspecialchars($s).'</textarea>
 			</div>
 			<div>
 				<button type="submit">Show me the characters</button>
 			</div>
 			</form>'.NL.
		    ViewChars::text2html($s).
    		// Information
		    '<div style="margin: -2.5em 0 4em 0;">'.mb_strlen($s).' characters, '.strlen($s).' bytes</div>'.NL;
	}
	
}

echo ViewChars::monkey();


?>

    <h2>Helpful Sites for Details on UTF Characters</h2>
    <ul>
        <li><a href="https://www.branah.com/unicode-converter" target="_blank">Branah.com Unicode Converter</a></li>
        <li><a href="http://www.fileformat.info/info/unicode/char/search.htm" target="_blank">FileFormat.Info</a></li>
        <li><a href="http://utf8-chartable.de/unicode-utf8-table.pl" target="_blank">utf8-chartable.de</a></li>
    </ul>
    
    <h2>Privacy Note</h2>
    <p>This web page (tool) does not store any information about you (no cookies, no IP logging) and it does not store any of the
    	text that is written or pasted into the box above.</p>
    <h2>Source Code</h2>
    <p>As this tools has received some attention on <a href="https://www.soscisurvey.de/tools/view-chars.php">soscisurvey.de</a>,
    	we chose to make the source code available on <a href="https://github.com/BurninLeo/see-non-printable-characters">GitHub</a>.</p>

</div>
</body>
</html>
