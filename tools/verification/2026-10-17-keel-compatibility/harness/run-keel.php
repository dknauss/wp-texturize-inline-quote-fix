<?php
// Disposable lab only. Requires real WordPress bootstrap through Studio WP-CLI.
$version = $GLOBALS['wp_version'];
require __DIR__ . '/reference-' . $version . '.php';
require __DIR__ . '/bridge.php';
$out = getenv('TYPOGRAPHY_OUT') ?: dirname(__DIR__) . '/out';
$mode = is_multisite() ? 'multisite' : 'single';
$native_sha256=hash_file('sha256',ABSPATH.WPINC.'/formatting.php');
$catalog_mode='version-matched core language install';
$rows = array(); $maps = array(); $summary = array();
$locales = array('en_US','de_DE','fr_FR','ru_RU','ar','ja');
$cases = array(
 'closing_apostrophe'=>"<strong>He</strong>'s here.",
 'opening_apostrophe'=>"I<strong>'ve been</strong>",
 'unicode_opening'=>"café<em>'s</em>",
 'combining_opening'=>"cafe\u{0301}<em>'s</em>",
 'uppercase_opening'=>"I<STRONG>'ve</STRONG>",
 'tab_opening'=>"I<strong\tclass=\"x\">'ve</strong>",
 'newline_opening'=>"I<em\nclass=\"x\">'ve</em>",
 'uppercase_closing'=>"<STRONG>He</STRONG>'s",
 'whitespace_closing'=>"<strong>He</strong >'s",
 'nested_closing'=>"<em><strong>He</strong></em>'s",
 'nested_opening'=>"I<em><strong>'ve</strong></em>",
 'quoted_name'=>"<em>\"John\"</em>'s book.",
 'closing_double'=>'"<em>John</em>"',
 'closing_single'=>"'<em>John</em>'",
 'plural_possessive'=>"<em>James</em>' book",
 'contraction_in_quote'=>"'He<em>'s</em> here'",
 'earlier_contraction'=>"'Don't <em>go</em>'",
 'ordinary_quotes'=>"word <em>'Hello'</em>",
 'no_word_empty'=>"<em></em>'Hello'",
 'no_word_punctuation'=>"<em>:</em>'Hello'",
 'space_before_quote'=>"<strong>He</strong> 'go'",
 'block_boundary'=>"word<div><em>'Hello'</em></div>",
 'comment_boundary'=>"word<!-- gap --><em>'Hello'</em>",
 'whitespace_boundary'=>"word<em> </em><strong>'Hello'</strong>",
 'self_closing'=>"word<span/><em>'Hello'</em>",
 'opaque_literal_entity'=>'<code><em>x</em>&#8216;s</code>',
 'opaque_ascii'=>"<code><em>x</em>'s -- ...</code>",
 'opaque_pre'=>"<pre><em>x</em>'s</pre>",
 'opaque_kbd'=>"<kbd><em>x</em>'s</kbd>",
 'opaque_script'=>"<script>var s = \"<em>x</em>&#8216;s\";</script>",
 'script_prose'=>"<script>if(a<b)window&&document</script>It's -- fine.",
 'math'=>"<math><annotation encoding=\"application/x-tex\">f'(x) - 1</annotation></math>",
 'mark'=>"<mark>He's -- here ...</mark>",
 'feet'=>'5\'6"', 'numeric_grouping'=>"10'000", 'quoted_number'=>"'7'",
 'dash'=>'A -- B; A--B; A - B; A --- B',
 'ordinary'=>"It's a 'test' ... -- (tm) 3x3.",
 'french_word'=>"l<em>'ami</em>",
 'cyrillic_word'=>"<em>слово</em>'s",
 'arabic_word'=>"<em>كتاب</em>'s",
 'japanese_word'=>"<em>日本語</em>'s",
 'authored_opening_entity'=>'<em>x</em>&#8216;Hello&#8217;',
 'authored_double_entity'=>'<em>x</em>&#8220;Hello&#8221;',
 'code_shortcode'=>"[code]<em>x</em>'s[/code]",
 'generated_shortcode'=>'[labquote]',
 'escaped_shortcode'=>"word[[code]]<em>'Hello'</em>",
);
add_shortcode('code', static function($atts,$content=''){return '<code>'.$content.'</code>';});
add_shortcode('labquote',static function(){return '<em>x</em>&#8216;s';});
$counts = array('http'=>0,'writes'=>0,'write_operations'=>array());
$http = static function($pre) use (&$counts){++$counts['http'];return new WP_Error('lab_http','External HTTP suppressed');};
$query = static function($sql) use (&$counts){if(preg_match('/^\s*(INSERT|UPDATE|DELETE|REPLACE|ALTER|CREATE|DROP|TRUNCATE)\b/i',$sql)){++$counts['writes'];preg_match('/^\s*(\w+)\s+(?:INTO\s+|FROM\s+)?([`a-z0-9_]+)/i',$sql,$op);$counts['write_operations'][]=array('operation'=>$op[1]??'','table'=>$op[2]??'');}return $sql;};
add_filter('pre_http_request',$http,999);
add_filter('query',$query);
$inventory=array();
foreach($GLOBALS['wp_filter'] as $name=>$hook){foreach($hook->callbacks as $priority=>$callbacks){foreach($callbacks as $cb){if($cb['function']==='wptexturize'){$inventory[$name]=array('priority'=>$priority,'bridge'=>false!==has_filter($name,'dk_texturize_inline_quote_fix'));}}}}
foreach($locales as $locale){
 $switched=switch_to_locale($locale);
 if(get_locale()!==$locale){throw new RuntimeException('Locale did not load: '.$locale);}
 $maps[$locale]=array('_catalog_sha256'=>file_exists(WP_LANG_DIR.'/'.$locale.'.mo')?hash_file('sha256',WP_LANG_DIR.'/'.$locale.'.mo'):null);
 foreach(array('opening curly double quote','closing curly double quote','opening curly single quote','closing curly single quote','apostrophe') as $ctx){
  $original=array('opening curly double quote'=>'&#8220;','closing curly double quote'=>'&#8221;','opening curly single quote'=>'&#8216;','closing curly single quote'=>'&#8217;','apostrophe'=>'&#8217;');
  $maps[$locale][$ctx]=_x($original[$ctx],$ctx);
 }
 wptexturize('reset',true);lab_wptexturize('reset',true);
 $different=0;$changed=0;$contracts=array();
 foreach($cases as $name=>$input){
  $native=wptexturize($input);$bridge=dk_texturize_inline_quote_fix($native);$reference=lab_wptexturize($input);
  $different += (int)($bridge!==$reference);$changed+=(int)($native!==$reference);
  $rows[]=compact('version','mode','locale','name','input','native','bridge','reference')+array('surface'=>'direct_postprocess');
 }
 $contracts['closing_apostrophe']=lab_wptexturize("<strong>He</strong>'s") === '<strong>He</strong>'._x('&#8217;','apostrophe').'s';
 $contracts['opening_apostrophe']=lab_wptexturize("I<strong>'ve</strong>") === 'I<strong>'._x('&#8217;','apostrophe').'ve</strong>';
 $contracts['opaque_entity']=lab_wptexturize('<code><em>x</em>&#8216;s</code>') === '<code><em>x</em>&#8216;s</code>';
 // Compare the actual filter pipeline; replace only core's callback at its existing priority.
 foreach(array('the_title','the_content','the_excerpt','the_excerpt_embed','comment_text','term_description','widget_text','the_content_feed','comment_text_rss','single_post_title','widget_title','wp_title') as $hook){
  foreach(array('closing_apostrophe','opening_apostrophe','opaque_literal_entity','generated_shortcode') as $name){
   $input=$cases[$name];
   remove_filter($hook,'dk_texturize_inline_quote_fix',12);
   $native=apply_filters($hook,$input);
   $present=array_key_exists($hook,$inventory)?$inventory[$hook]['bridge']:false;
   if($present){add_filter($hook,'dk_texturize_inline_quote_fix',12);}
   $bridge=apply_filters($hook,$input);
   if($present){remove_filter($hook,'dk_texturize_inline_quote_fix',12);}
   $priority=has_filter($hook,'wptexturize');
   if(false!==$priority){foreach($GLOBALS['wp_filter'][$hook]->callbacks[$priority] as &$cb){if($cb['function']==='wptexturize'){$cb['function']='lab_wptexturize';}}unset($cb);}
   $reference=apply_filters($hook,$input);
   if(false!==$priority){foreach($GLOBALS['wp_filter'][$hook]->callbacks[$priority] as &$cb){if($cb['function']==='lab_wptexturize'){$cb['function']='wptexturize';}}unset($cb);}
   if($present){add_filter($hook,'dk_texturize_inline_quote_fix',12);}
   $rows[]=compact('version','mode','locale','name','input','native','bridge','reference')+array('surface'=>$hook);
  }
 }
 $summary[$locale]=array('different'=>$different,'reference_changes'=>$changed,'contracts'=>$contracts);
 if($switched){restore_previous_locale();}
}
// Stock static locale cache behavior: deliberately no reset after switching.
switch_to_locale('de_DE');wptexturize('reset',true);lab_wptexturize('reset',true);
$cache_before=array(wptexturize("'Hallo'"),lab_wptexturize("'Hallo'"));
restore_previous_locale();switch_to_locale('en_US');
$cache_after=array(wptexturize("'Hello'"),lab_wptexturize("'Hello'"));
wptexturize('reset',true);lab_wptexturize('reset',true);
$cache_expected=array(wptexturize("'Hello'"),lab_wptexturize("'Hello'"));restore_previous_locale();
file_put_contents($out.'/'.$mode.'-'.$version.'-keel.json',wp_json_encode(compact('version','mode','maps','summary','rows','inventory','counts','cache_before','cache_after','cache_expected','native_sha256','catalog_mode'),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
echo wp_json_encode(array('version'=>$version,'mode'=>$mode,'locales'=>count($locales),'rows'=>count($rows),'summary'=>$summary,'counts'=>$counts));
