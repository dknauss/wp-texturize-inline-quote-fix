<?php
$version=$GLOBALS['wp_version'];require __DIR__.'/reference-'.$version.'.php';require __DIR__.'/bridge.php';
if(!function_exists('keel_defaults_hide_shortcode_residue')){throw new RuntimeException('Keel not loaded');}
$out = getenv('TYPOGRAPHY_OUT') ?: dirname(__DIR__) . '/out';
function lab_cleanup_passthrough($value){return $value;}
$priority=has_filter('the_content','keel_defaults_hide_shortcode_residue');
if(false===$priority){throw new RuntimeException('Keel cleanup callback not installed');}
wp_set_current_user(0);
$id=wp_insert_post(array('post_status'=>'publish','post_title'=>'Typography cleanup fixture','post_content'=>'fixture'));
$GLOBALS['post']=get_post($id);setup_postdata($GLOBALS['post']);
add_shortcode('labquote',static function(){return '<em>x</em>&#8216;s';});
$cases=array('missing'=>"[labmissing x=1] <strong>He</strong>'s",'registered'=>"[labquote]",'opaque'=>'<code><em>x</em>&#8216;s</code>','paired'=>"[labmissing]<strong>He</strong>'s[/labmissing]");$rows=array();
foreach(array('en_US','de_DE','fr_FR','ru_RU','ar','ja')as$locale){
 $switched=switch_to_locale($locale);wptexturize('reset',true);lab_wptexturize('reset',true);
 foreach(array(false,true)as$cleanup){
  foreach($GLOBALS['wp_filter']['the_content']->callbacks[$priority]as&$cb){if(in_array($cb['function'],array('keel_defaults_hide_shortcode_residue','lab_cleanup_passthrough'),true)){$cb['function']=$cleanup?'keel_defaults_hide_shortcode_residue':'lab_cleanup_passthrough';}}unset($cb);
  foreach(array('native','bridge','patch')as$arm){
   remove_filter('the_content','dk_texturize_inline_quote_fix',12);
   if('bridge'===$arm){add_filter('the_content','dk_texturize_inline_quote_fix',12);}
   foreach($GLOBALS['wp_filter']['the_content']->callbacks[10]as&$cb){if(in_array($cb['function'],array('wptexturize','lab_wptexturize'),true)){$cb['function']='patch'===$arm?'lab_wptexturize':'wptexturize';}}unset($cb);
   foreach($cases as$name=>$input){$output=apply_filters('the_content',$input);$rows[]=compact('locale','cleanup','arm','name','input','output');}
  }
 }
 if($switched){restore_previous_locale();}
}
wp_delete_post($id,true);wp_reset_postdata();
file_put_contents($out.'/cleanup-'.$version.'.json',wp_json_encode(compact('version','priority','rows'),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo wp_json_encode(array('version'=>$version,'rows'=>count($rows),'cleanup_priority'=>$priority));
