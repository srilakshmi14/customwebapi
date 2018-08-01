<?php

namespace Drupal\custom_restapi\Plugin\rest\resource;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\user\Entity\User;
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
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Password\PasswordInterface;
/**
 * Provides a resource to perform user related operations
 *
 * @RestResource(
 *   id = "user_details",
 *   label = @Translation("User Details"),
 *   serialization_class = "Drupal\user\Entity\User",
 *   uri_paths = {
 *     "canonical" = "/user_details/{user}",
 *   }
 * )
 */
class UserDetails extends ResourceBase {

 use EntityResourceValidationTrait;
  use EntityResourceAccessTrait;

/**
   *  A curent user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;
  /**
   *  A instance of entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

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
      $container->get('logger.factory')->get('rest'),
      $container->get('current_user')
    );
  }

  /*
   * Responds to GET requests.
   *
   * Returns a list of users.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing a list of users.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
   public function get() {
   
     $permission = 'access user profiles';

      if(!$this->currentUser->hasPermission($permission)) {
       throw new AccessDeniedHttpException('This user needs '.$permission. 'permission' );
       //return new ResourceResponse("No permission");
      }

      $ids = \Drupal::entityQuery('user')
             ->execute();

      $users = User::loadMultiple($ids);


        return new ResourceResponse($users);
    }


   /**
   *  POST method to create user accounts
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the user object
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException

   **/

   public function post($data){
 
    $this->checkEditFieldAccess($data);

    // Validate the received data before saving.
    $this->validate($data);
    
    try{
    $user = User::create();
    $user->setPassword($data->pass->value);
    $user->enforceIsNew();
    $user->setEmail($data->mail->value);
    $user->setUsername($data->name->value);
    $user->save();
     return new ResourceResponse($user, 201);

  }

  catch (EntityStorageException $e) {
      throw new HttpException(500, 'Internal Server Error', $e);
    }
  

   } 

  /*
   * Responds to PATCH requests.
   *
   * Returns the user details that got edited.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing modified user details
   *
   * 
   */


  public function patch($data){

    $uid = $data->uid->value;
    $user = User::load($uid);
    //Update email Id
   $user->setEmail($data->mail->value);
   $user->setUsername($data->name->value);
   $user->setPassword($data->pass->value);
   $users = $user->save();
   return new ResourceResponse($user);

 }

  /**

   * DELETE method for deleting the user account

 **/

 public function delete($uid){
 
   try{
   $user = User::load($uid);  
   user_delete($uid); 
 return new ResourceResponse(NULL,204);
 }

 catch (EntityStorageException $e) {
      throw new HttpException(500, 'Internal Server Error', $e);
    }
 }


}
