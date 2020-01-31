<?php
/**
 * Newsletter2Go plugin for Craft CMS 3.x
 *
 * API integration with Newsletter2Go
 *
 * @link      https://niklassonnenschein.de
 * @copyright Copyright (c) 2020 Niklas Sonnenschein
 */

namespace hfg\newsletter2go\models;

use hfg\newsletter2go\Newsletter2Go;

use Craft;
use craft\base\Model;

/**
 * Newsletter2Go Settings Model
 *
 * This is a model used to define the plugin's settings.
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Niklas Sonnenschein
 * @package   Newsletter2Go
 * @since     1.0.0
 */
class Contact extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * Some field model attribute
     *
     * @var string
     */
    public $name = "";
    public $email = "";

    // Public Methods
    // =========================================================================

    public function attributeLabels()
    {
        return [
            'name' => \Craft::t('newsletter2-go', 'Name'),
            'email' => \Craft::t('newsletter2-go', 'E-Mail')
        ];
    }

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['email'], 'required'],
            [['email'], 'email', "message" => \Craft::t("newsletter2-go", "{attribute} is not a valid address.")],
        ]);
    }
}
