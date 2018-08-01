<?php
namespace Drupal\custom_restapi\Plugin\rest\resource;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityStorageException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "node_details",
 *   label = @Translation("Node Details"),
 *   serialization_class = "Drupal\node\Entity\Node",
 *   uri_paths = {
 *     "canonical" = "api/node/{id}",
        "https://www.drupal.org/link-relations/create" = "/api/node"

 *   }
 * )
 */
class NodeDetails extends ResourceBase {
  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
 
   use EntityResourceValidationTrait;
  use EntityResourceAccessTrait;
 
  protected $currentUser;
  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('create_new_node'),
      $container->get('current_user')
    );
  }

  public function get($data){

    if (!$this->currentUser->hasPermission('access content')) {
//      throw new AccessDeniedHttpException("Permiision for access content is required");
       $error = array('message' => 'You do not have permission to access the contents.', 'code' => 'e3', 'response_status' => 'failure');
       return new JsonResponse($error); 
    }


    $nodes = Node::load($data);

    //Check if node exists or not.
  
  if(  $this->validateResponse($nodes)){
   foreach($nodes as $key=>$value){
    $k[] = $key;
    $v[] = $value;
   }
  for($i=0;$i<count($k);$i++){
  $values[] = $nodes->get($k[$i])->value;
  $val = array_combine($k,$values);
  $target_id[] = $nodes->get($k[$i])->target_id;
  }

  foreach($val as $key=>$value)
   {
    if(empty($value))
       $target_key[] = $key;
  }

  $target_val = array_filter($target_id);

  $length = count($target_key)-count($target_val);

  for($i=0;$i<$length;$i++){
   array_push($target_val,'');
  }

  $target_array = array_combine($target_key, $target_val);

  $node_details = array_merge(array_filter($val),$target_array);

  $message = array('message'=> 'Please find the  '.$nodes->title->value.' details here!!', 'code'=> 's1', 'response_status'=>'success');

  $response = array_merge($message,$node_details);
      return new JsonResponse($response); 
  }
  else{
   $message = array('message'=>"Sorry..The node you requested does not exists!", 'code'=>'e1', 'response_status'=>'Failure');
   return new JsonResponse($message);
   }

 }

  /**
   * Responds to POST requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param $node_type
   * @param $data
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   * Throws exception expected.
   */
  public function post($data) {
    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException("Permiision for access content is required");
    }
 
      $check_error = $this->errors($data);
       
       if(!empty($check_error)){

       return new JsonResponse($check_error);

       }

       $node = Node::create(
       array(
        'type' => $data->type->target_id,
        'title' => $data->title->value,
        'field_amount'=> $data->field_amount->value,
        'field_location' => $data->field_location->value,
        'field_name_of_the_director' => $data->field_name_of_the_director->value,
        'field_valied_years' =>  $data->field_valied_years->value
       )
     );
    $node->save();

    foreach($node as $key=>$value){
    $k[] = $key;
    $v[] = $value;
   }
  for($i=0;$i<count($k);$i++){
  $values[] = $node->get($k[$i])->value;
  $val = array_combine($k,$values);
  $target_id[] = $node->get($k[$i])->target_id;
  }

  foreach($val as $key=>$value)
   {
    if(empty($value))
       $target_key[] = $key;
  }

  $target_val = array_filter($target_id);

  $length = count($target_key)-count($target_val);

  for($i=0;$i<$length;$i++){
   array_push($target_val,'');
  }

  $target_array = array_combine($target_key, $target_val);

  $node_details = array_merge(array_filter($val),$target_array);

  $message = array('message'=> 'The Node '.$node->title->value.' has been created successfully', 'code'=> 's2', 'response_status'=>'success');

  $response = array_merge($message,$node_details);
      return new JsonResponse($response);

//      return new ResourceResponse($node,201);
  // }   



  }

   
   public function patch($nid,$data){
    
    $nodes = Node::load($nid);
   // $this->validateResponse($nodes);
    if(  $this->validateResponse($nodes)){  
 

    $check_error = $this->errors($data);

       if(!empty($check_error)){

       return new JsonResponse($check_error);

       }

    $nodes->set('title', $data->title->value);
    $nodes->set('field_amount', $data->field_amount->value);
    $nodes->set('field_location', $data->field_location->value);
    $nodes->set('field_name_of_the_director', $data->field_name_of_the_director->value);
    $nodes->set('field_valied_years', $data->field_valied_years->value);
   $nodes->save();
  $msg = array('msg'=>'The node '. $nodes->title->value. ' has been updated','code'=>'s3', 'response_status'=>'success');
 $j = array_merge($msg, (array)$nodes); 
  return new JsonResponse($msg);
}


  else{
  $message = array('message'=>"Sorry..The node you requested does not exists!", 'code'=>'e1', 'response_status'=>'Failure');
   return new JsonResponse($message);

  }

  }

  public function delete($nid){
     
  
    $nodes = Node::load($nid);
   if(  $this->validateResponse($nodes)){

//    $this->validateResponse($nodes);
    $nodes->delete();
   $response = array('message'=>'The node '. $nodes->title->value. ' has been deleted', 'code'=>'s4', 'response_status'=>'success');    
   return new jsonResponse($response);
  }

 else{
  $message = array('message'=>"Sorry..The node you requested does not exists!", 'code'=>'e1', 'response_status'=>'Failure');
   return new JsonResponse($message);
 
 }

  }

}
