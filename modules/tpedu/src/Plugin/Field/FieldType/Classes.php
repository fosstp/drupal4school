namespace Drupal\tpedu\Plugin\Field\FieldType;

use Drupal\Core\Field\ListItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'tpedu_classes' field type.
 *
 * @FieldType(
 *   id = "tpedu_classes",
 *   label = "班級",
 *   description = "全校班級列表",
 *   category = "臺北市教育人員",
 *   default_widget = "options_select",
 *   default_formatter = "classes_default"
 * )
 */
class Classes extends ListItemBase {



}