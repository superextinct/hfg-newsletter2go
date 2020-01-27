<?php
/**
 * Newsletter2Go plugin for Craft CMS 3.x
 *
 * API integration with Newsletter2Go
 *
 * @link      https://niklassonnenschein.de
 * @copyright Copyright (c) 2020 Niklas Sonnenschein
 */

namespace hfg\newsletter2go\controllers;

use hfg\newsletter2go\Newsletter2Go;
use hfg\newsletter2go\models\Contact;
use hfg\newsletter2go\services\ApiService;

use Craft;
use craft\web\Controller;
use yii\web\Response;

/**
 * SubscriptionController Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Niklas Sonnenschein
 * @package   Newsletter2Go
 * @since     1.0.0
 */
class SubscriptionController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = true;


    // Public Methods

    /**
     * Handle a request going to our plugin's index action URL,
     * e.g.: actions/newsletter2-go/subscription-controller
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $settings = Newsletter2Go::$plugin->getSettings();

        $name = $request->getBodyParam("recipientName");
        $email = $request->getBodyParam("recipientEmail");

        $contact = new Contact();
        $contact->name = $request->getBodyParam("recipientName");
        $contact->email = $request->getBodyParam("recipientEmail");

        if($contact->validate()) {
            $response = Newsletter2Go::$plugin->apiService->subscribe($contact);

            if ($response->status == "201") {
                return $this->asJson(["success" => $settings->successFlashMessage]);
            } else {
                return $this->asJson(["errors" => $response]);
            }
        } else {
            return $this->asJson(["errors" => $contact->getErrors()]);
        }

        Craft::$app->session->setFlash("notice", $settings->successFlashMessage);
        return $this->redirectToPostedUrl("/");
        
    }
}
