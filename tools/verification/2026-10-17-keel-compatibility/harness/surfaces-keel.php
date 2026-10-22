<?php
$version=$GLOBALS['wp_version'];require __DIR__.'/reference-'.$version.'.php';require __DIR__.'/bridge.php';
$out = getenv('TYPOGRAPHY_OUT') ?: dirname(__DIR__) . '/out';
$input="<strong>He</strong>'s here. I<em>'ve</em> said 'go'.";
$id=wp_insert_post(array('post_title'=>$input,'post_content'=>$input,'post_excerpt'=>$input,'post_status'=>'publish','post_type'=>'post'));
if(is_wp_error($id)||!$id){throw new RuntimeException('post creation failed');}
$GLOBALS['post']=get_post($id);setup_postdata($GLOBALS['post']);$rows=array();
foreach(array('en_US','de_DE','fr_FR','ru_RU','ar','ja')as$locale){
 $switched=switch_to_locale($locale);wptexturize('reset',true);lab_wptexturize('reset',true);
 foreach(array('native','bridge','patch')as$arm){
  $changed=array();
  foreach($GLOBALS['wp_filter']as$hook=>$obj){
   remove_filter($hook,'dk_texturize_inline_quote_fix',12);
   if('patch'===$arm){$p=has_filter($hook,'wptexturize');if(false!==$p){foreach($GLOBALS['wp_filter'][$hook]->callbacks[$p] as &$cb){if($cb['function']==='wptexturize'){$cb['function']='lab_wptexturize';}}unset($cb);$changed[$hook]=$p;}}
  }
  if('bridge'===$arm){foreach(array('comment_text','term_description','the_content','the_excerpt','the_excerpt_embed','the_title','widget_text')as$hook){add_filter($hook,'dk_texturize_inline_quote_fix',12);}}
  $request=new WP_REST_Request('GET','/wp/v2/posts/'.$id);$response=rest_do_request($request);
  $data=$response->get_data();
  $rows[]=array('locale'=>$locale,'arm'=>$arm,'title'=>get_the_title($id),'content'=>apply_filters('the_content',get_post_field('post_content',$id)),'excerpt'=>apply_filters('the_excerpt',get_the_excerpt($id)),'feed'=>get_the_content_feed('rss2'),'rest_status'=>$response->get_status(),'rest_title'=>$data['title']['rendered']??null,'rest_content'=>$data['content']['rendered']??null,'rest_excerpt'=>$data['excerpt']['rendered']??null,'raw'=>get_post_field('post_content',$id));
  foreach($changed as$hook=>$p){foreach($GLOBALS['wp_filter'][$hook]->callbacks[$p] as &$cb){if($cb['function']==='lab_wptexturize'){$cb['function']='wptexturize';}}unset($cb);}
 }
 if($switched){restore_previous_locale();}
}
wp_delete_post($id,true);wp_reset_postdata();
file_put_contents($out.'/surfaces-'.(is_multisite()?'multisite':'single').'-'.$version.'-keel.json',wp_json_encode($rows,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo wp_json_encode(array('rows'=>count($rows),'rest_statuses'=>array_unique(array_column($rows,'rest_status')),'stored_content_unchanged'=>count(array_filter($rows,static function($r)use($input){return $r['raw']!==$input;}))===0));
