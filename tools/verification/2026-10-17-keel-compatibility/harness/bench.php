<?php
$version=$GLOBALS['wp_version'];
require __DIR__.'/reference-'.$version.'.php';require __DIR__.'/bridge.php';
$out = getenv('TYPOGRAPHY_OUT') ?: dirname(__DIR__) . '/out';
$mode=is_multisite()?'multisite':'single';
$fixtures=array(
 'prose'=>str_repeat('Ordinary prose with several words and no inline delimiters. ',60),
 'html'=>str_repeat('<p>A normal <strong>paragraph</strong> with <em>inline text</em> and a link <a href="/">here</a>.</p>',40),
 'quotes'=>str_repeat("<p>\"<em>John</em>\" and <strong>He</strong>'s here; I<em>'ve</em> said 'go'.</p>",50),
);
$http=0;$writes=0;
add_filter('pre_http_request',static function($pre)use(&$http){++$http;return new WP_Error('lab','blocked');},999);
add_filter('query',static function($s)use(&$writes){if(preg_match('/^\s*(INSERT|UPDATE|DELETE|REPLACE|CREATE|ALTER|DROP|TRUNCATE)\b/i',$s)){++$writes;}return $s;});
$results=array();mt_srand(18549);
foreach(array('en_US','de_DE','fr_FR','ru_RU','ar','ja')as$locale){
 $switched=switch_to_locale($locale);wptexturize('reset',true);lab_wptexturize('reset',true);
 foreach($fixtures as$name=>$input){
  $samples=array('native'=>array(),'bridge'=>array(),'patch'=>array());$peaks=$samples;$queries=$samples;
  for($round=-5;$round<30;++$round){
   $arms=array('native','bridge','patch');shuffle($arms);
   foreach($arms as$arm){
    memory_reset_peak_usage();$q=$GLOBALS['wpdb']->num_queries;$start=hrtime(true);
    for($n=0;$n<10;++$n){
     if('patch'===$arm){$value=lab_wptexturize($input);}elseif('bridge'===$arm){$value=dk_texturize_inline_quote_fix(wptexturize($input));}else{$value=wptexturize($input);}
    }
    $ms=(hrtime(true)-$start)/1e6/10;
    if($round>=0){$samples[$arm][]=$ms;$peaks[$arm][]=memory_get_peak_usage(true);$queries[$arm][]=$GLOBALS['wpdb']->num_queries-$q;}
   }
  }
  $results[]=compact('version','mode','locale','name','samples','peaks','queries')+array('bytes'=>strlen($input));
 }
 if($switched){restore_previous_locale();}
}
file_put_contents($out.'/bench-'.$mode.'-'.$version.'.json',wp_json_encode(compact('results','http','writes'),JSON_PRETTY_PRINT));
echo wp_json_encode(array('version'=>$version,'mode'=>$mode,'fixture_locale_pairs'=>count($results),'http'=>$http,'writes'=>$writes));
