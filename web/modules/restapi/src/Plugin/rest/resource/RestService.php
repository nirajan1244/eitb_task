<?php

declare(strict_types=1);

namespace Drupal\restapi\Plugin\rest\resource;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;
use Drupal\node\Entity\Node;
/**
 * Represents restrevice records as resources.
 *
 * @RestResource (
 *   id = "restservice",
 *   label = @Translation("restrevice"),
 *   uri_paths = {
 *     "canonical" = "/api/restservice",
 *     "create" = "/api/restservice"
 *   }
 * )
 *
 * @DCG
 * The plugin exposes key-value records as REST resources. In order to enable it
 * import the resource configuration into active configuration storage. An
 * example of such configuration can be located in the following file:
 * core/modules/rest/config/optional/rest.resource.entity.node.yml.
 * Alternatively, you can enable it through admin interface provider by REST UI
 * module.
 * @see https://www.drupal.org/project/restui
 *
 * @DCG
 * Notice that this plugin does not provide any validation for the data.
 * Consider creating custom normalizer to validate and normalize the incoming
 * data. It can be enabled in the plugin definition as follows.
 * @code
 *   serialization_class = "Drupal\foo\MyDataStructure",
 * @endcode
 *
 * @DCG
 * For entities, it is recommended to use REST resource plugin provided by
 * Drupal core.
 * @see \Drupal\rest\Plugin\rest\resource\EntityResource
 */
final class RestService extends ResourceBase {

  /**
   * The key-value storage.
   */
  private readonly KeyValueStoreInterface $storage;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    KeyValueFactoryInterface $keyValueFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->storage = $keyValueFactory->get('restservice');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('keyvalue')
    );
  }

  /**
   * Responds to POST requests and saves the new record.
   */
  public function post(array $data): ModifiedResourceResponse {
    $data['id'] = $this->getNextId();
    $this->storage->set($data['id'], $data);
    $this->logger->notice('Created new restrevice record @id.', ['@id' => $data['id']]);
    // Return the newly created record in the response body.
    return new ModifiedResourceResponse($data, 201);
  }

  /**
   * Responds to GET requests.
   */
  public function get(): ResourceResponse {
   /* if (!$this->storage->has($id)) {
      throw new NotFoundHttpException();
    }
    $resource = $this->storage->get($id);*/


        $query = \Drupal::entityQuery('node')
            ->condition('type', 'teachers')
            ->accessCheck(TRUE);
            $results = $query->execute();
    $data_array=[];
    foreach($results as $node_id){
      $node = Node::load($node_id);
      $paragraph = $node->field_teachers->getValue();
      // Loop through the result set.
      foreach ( $paragraph as $element ) {
        $p = \Drupal\paragraphs\Entity\Paragraph::load( $element['target_id'] );
        $temp_array=[];
        $temp_array["field_designation"] = $p->field_designation->getValue();
        $temp_array["field_facebook_link"] = $p->field_facebook_link->getValue();
        $temp_array["field_instagram_link"] = $p->field_instagram_link->getValue();
        $temp_array["field_teacher_full_name"] = $p->field_teacher_full_name->getValue();
        $temp_array["field_twitter_link"] = $p->field_twitter_link->getValue();
        $temp_array["field_picture"] = \Drupal::service('file_url_generator')->generateAbsoluteString($p->get('field_picture')->entity->uri->value);
        $data_array[] = $temp_array;
      }
    }
    $json = json_encode($data_array);


    return new ResourceResponse($json);
  }

  /**
   * Responds to PATCH requests.
   */
  public function patch($id, array $data): ModifiedResourceResponse {
    if (!$this->storage->has($id)) {
      throw new NotFoundHttpException();
    }
    $stored_data = $this->storage->get($id);
    $data += $stored_data;
    $this->storage->set($id, $data);
    $this->logger->notice('The restrevice record @id has been updated.', ['@id' => $id]);
    return new ModifiedResourceResponse($data, 200);
  }

  /**
   * Responds to DELETE requests.
   */
  public function delete($id): ModifiedResourceResponse {
    if (!$this->storage->has($id)) {
      throw new NotFoundHttpException();
    }
    $this->storage->delete($id);
    $this->logger->notice('The restrevice record @id has been deleted.', ['@id' => $id]);
    // Deleted responses have an empty body.
    return new ModifiedResourceResponse(NULL, 204);
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method): Route {
    $route = parent::getBaseRoute($canonical_path, $method);
    // Set ID validation pattern.
    if ($method !== 'POST') {
      $route->setRequirement('id', '\d+');
    }
    return $route;
  }

  /**
   * Returns next available ID.
   */
  private function getNextId(): int {
    $ids = \array_keys($this->storage->getAll());
    return count($ids) > 0 ? max($ids) + 1 : 1;
  }

}
