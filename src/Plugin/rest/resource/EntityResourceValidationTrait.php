<?php

namespace Drupal\custom_restapi\Plugin\rest\resource;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @internal
 * @todo Consider making public in https://www.drupal.org/node/2300677
 */
trait EntityResourceValidationTrait {

  /**
   * Verifies that the whole entity does not violate any validation constraints.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to validate.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
   *   If validation errors are found.
   */
  protected function validate(EntityInterface $entity) {
    // @todo Remove when https://www.drupal.org/node/2164373 is committed.
    if (!$entity instanceof FieldableEntityInterface) {
      return;
    }
    $violations = $entity->validate();

    // Remove violations of inaccessible fields as they cannot stem from our
    // changes.
    $violations->filterByFieldAccess();

    if ($violations->count() > 0) {
      $message = "Unprocessable Entity: validation failed.\n";
      foreach ($violations as $violation) {
        // We strip every HTML from the error message to have a nicer to read
        // message on REST responses.
        $message .= $violation->getPropertyPath() . ': ' . PlainTextOutput::renderFromHtml($violation->getMessage()) . "\n";
      }
      throw new UnprocessableEntityHttpException($message);
   
    }
  }

  public function validateResponse($node){
   $message = array('message'=>"Sorry..The node you requested does not exists!");
   if(empty($node)){
    
    return false;

   }

  else{
   return true;
  } 

  }

  public function errors($data){

     $entity_type_id = 'node';
  $bundle = $data->type->target_id;
  $msg = 'Unable to process the request.';

  if(empty($data->title->value)){
   $message = array('message' => $msg." title is a required field", 'code'=>'e2', 'response_status'=>'failure');
   return $message;
  
   }

  foreach (\Drupal::entityManager()->getFieldDefinitions($entity_type_id, $bundle) as $field_name => $field_definition) {
    if (!empty($field_definition->getTargetBundle())) {
      $bundleFields[$entity_type_id][$field_name]['type'] = $field_definition->getType();
   
   if($bundleFields[$entity_type_id][$field_name]['type'] != gettype($data->$field_name->value)){
      $message = array('message'=> $msg.$field_name. " should be of type ".$bundleFields[$entity_type_id][$field_name]['type'].". ".gettype($data->$field_name->value)." given.", 'code'=>'e3','response_status'=>'failure');
     
      return $message;
    }
    

    }
  }

 }



}
