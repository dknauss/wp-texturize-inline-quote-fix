<?php
$version=$GLOBALS['wp_version'];require __DIR__.'/reference-'.$version.'.php';require __DIR__.'/bridge.php';
if(!is_multisite()){throw new RuntimeException('Expected multisite');}
$out = getenv('TYPOGRAPHY_OUT') ?: dirname(__DIR__) . '/out';$records=array();
foreach(array('/german/'=>'de_DE','/french/'=>'fr_FR')as$path=>$locale){
 $site=get_sites(array('path'=>$path,'number'=>1));
 $id=$site?(int)$site[0]->blog_id:wpmu_create_blog(get_network()->domain,$path,'Typography '.$locale,1,array('WPLANG'=>$locale),get_current_network_id());
 if(is_wp_error($id)){throw new RuntimeException($id->get_error_message());}
 update_blog_option($id,'WPLANG',$locale);
}
$root=get_current_blog_id();
wptexturize('reset',true);lab_wptexturize('reset',true);
foreach(get_sites(array('number'=>10))as$site){
 switch_to_blog((int)$site->blog_id);
 $locale=get_option('WPLANG')?:'en_US';
 $without_locale_switch=array('locale'=>get_locale(),'native'=>wptexturize("'<em>Hello</em>'"),'patch'=>lab_wptexturize("'<em>Hello</em>'"));
 $switched=switch_to_locale($locale);
 $without_reset=array('native'=>wptexturize("'<em>Hello</em>'"),'patch'=>lab_wptexturize("'<em>Hello</em>'"));
 wptexturize('reset',true);lab_wptexturize('reset',true);
 $with_reset=array('native'=>wptexturize("'<em>Hello</em>'"),'bridge'=>dk_texturize_inline_quote_fix(wptexturize("'<em>Hello</em>'")),'patch'=>lab_wptexturize("'<em>Hello</em>'"));
 $records[]=compact('locale','without_locale_switch','without_reset','with_reset')+array('blog_id'=>get_current_blog_id());
 if($switched){restore_previous_locale();}restore_current_blog();
}
$restored=array('blog'=>get_current_blog_id()===$root,'locale'=>get_locale());
file_put_contents($out.'/network-'.$version.'.json',wp_json_encode(compact('records','restored'),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo wp_json_encode(compact('version','records','restored'));
