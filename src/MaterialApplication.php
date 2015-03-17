<?php
namespace samson\cms\web\material;

use samson\cms\CMSNavMaterial;
/*use samsonos\cms\ui\MenuItem;*/

/**
 * SamsonCMS generic material application.
 *
 * This application covers all actions that can be done
 * with materials and related entities in SamsonCMS.
 *
 * @package samson\cms\web\material
 */
class MaterialApplication extends \samson\cms\App
{
    /** Application name */
    public $name = 'Материалы';

    /** Identifier */
    protected $id = 'material';

    /** Table rows count */
    protected $table_rows = 15;

    /**
     * Handler for rendering UI main-menu
     * @param \samsonos\cms\ui\Menu $menu Pointer to menu container
     * @param \samsonos\cms\ui\Container $workspace Pointer to main container
     */
    /*public function uiMainMenu(&$menu, &$workspace)
    {
        // Create site item
        $siteItem = new MenuItem($menu);
        $siteItem->set('title', t('Перейти к материалам веб-сайта', true))
            ->set('class', 'btn-application')
            ->set('content', '<a href="'.url()->build($this->id).'">'.t($this->app_name, true).'</a>')
        ;
    }*/

    /**
     * Handler for rendering UI main-menu
     * @param \samsonos\cms\ui\Menu $menu Pointer to menu container
     * @param \samsonos\cms\ui\Container $workspace Pointer to main container
     */
    /*public function uiSubMenu(&$menu, &$workspace)
    {
        // Create site item
        $showAll = new MenuItem($menu);
        $showAll->set('title', t('Отобразить все материалы веб-сайта', true))
            ->set('content', '<a href="'.url()->build($this->id).'">'.t('Все', true).'</a>')
        ;

        // Create site item
        $addNew = new MenuItem($menu);
        $addNew->set('title', t('Создать новый материал для веб-сайта', true))
            ->set('content', '<a href="'.url()->build($this->id, 'form', 0, 0).'">'.t('Новый материал', true).'</a>')
        ;
    }*/

    /**
     * Module initialization
     * @param array $params Collection of parameters
     * @return bool|void
     */
    public function init(array $params = array())
    {
       /* // Subscribe for main-menu rendering
        \samson\core\Event::subscribe('cms_ui.mainmenu_leftcreated', array($this, 'uiMainMenu'));
        // Subscribe for sub-menu rendering
        \samson\core\Event::subscribe('cms_ui.submenu_created', array($this, 'uiSubMenu'));*/

        return parent::init($params);
    }

    /** Controllers */

    /** Generic controller */
    public function __handler($cmsnav = null, $search = null, $page = null)
    {
        // Generate localized title
        $title = t($this->name, true);

        // Set view scope
        $renderer = $this->view('index');

        // Try to find cmsnav
        if (isset($cmsnav) && dbQuery('\samson\cms\Navigation')->id($cmsnav)->first($cmsnav)) {
            // Add structure title
            $title = t($cmsnav->Name, true).' - '.$title;

            // Pass Navigation to view
            $this->cmsnav($cmsnav);
        }

        // Old-fashioned direct search input form POST if not passed
        $search = !isset($search) ? (isset($_POST['search']) ? $_POST['search'] : '') : $search;

        if (!isset($cmsnav)) {
            $this->all_materials(true);
        }

        // Set view data
        $renderer
            ->title($title)
            ->search($search)
            ->set($this->__async_table($cmsnav, $search, $page))
        ;
    }

    /** Generic material form controller */
    public function __form( $material_id = null, $cmsnav = null )
    {
        // If this is form for a new material with structure relation
        if ($material_id == 0 && isset($cmsnav)) {
            // Create new material db record
            $material = new \samson\cms\CMSMaterial(false);
            $material->Active = 1;
            $material->Created = date('Y-m-d H:m:s');

            $user = m('social')->user();
            $material->UserID = $user->UserID;
            $material->save();
            
            // Set new material as current
            $material_id = $material->id;

            // Convert parent CMSNavigation to an array
            $cmsnavs = !is_array($cmsnav) ? array($cmsnav) : $cmsnav;

            // Fill parent CMSNavigation relations for material
            foreach ($cmsnavs as $cmsnav) {
                // Create relation with structure
                $structureMaterial = new \samson\activerecord\structurematerial(false);
                $structureMaterial->MaterialID = $material->id;
                $structureMaterial->StructureID = $cmsnav;
                $structureMaterial->Active = 1;
                $structureMaterial->save();
            }
        }

        // Create form object
        $form = new \samson\cms\web\material\Form( $material_id, $cmsnav );

        if ($material_id == 0) {
            $this->new_material(true);
        }
        // Render form
        $this->html( $form->render() );
    }

    /** Main logic */

    /** Async form */
    function __async_form( $material_id = null, $cmsnav = null )
    {
        // Create form object
        $form = new \samson\cms\web\material\Form( $material_id );

        // Success
        return array( 'status' => TRUE, 'form' => $form->render(), 'url' => $this->id.'/form/'.$material_id );
    }

    /** Async materials save */
    function __async_save()
    {
        // If we have POST data
        if( isset($_POST) )
        {
            // Create empty object
            /* @var $db_material \samson\cms\CMSMaterial */
            $db_material = new \samson\cms\CMSMaterial(false);

            // If material identifier is passed and it's valid
            if( isset($_POST['MaterialID']) && $_POST['MaterialID'] > 0 )
            {
                $db_material = dbQuery('samson\cms\cmsmaterial')->id($_POST['MaterialID'])->first();
            }
            // New material creation
            else
            {
                // Fill creation ts
                $db_material->Created = date('h:m:i d.m.y');
                $db_material->Active = 1;
            }

            // Make it not draft
            $db_material->Draft = 0;

            if( isset( $_POST['Name'] )) 		$db_material->Name = $_POST['Name'];
            if( isset( $_POST['Published'] )) 	$db_material->Published = $_POST['Published'];
            if( isset( $_POST['type'] )) 	$db_material->type = $_POST['type'];
            if( isset( $_POST['Url'] )) 		$db_material->Url = $_POST['Url'];

            // Save object to DB
            $db_material->save();

            // Clear existing relations between material and cmsnavs
            foreach ( dbQuery('samson\cms\CMSNavMaterial')->MaterialID( $db_material->id )->exec() as $cnm ) $cnm->delete();

            // Iterate relations between material and cmsnav
            if( isset( $_POST['StructureID'] )) foreach( $_POST['StructureID'] as $cmsnav_id )
            {
                // Save record
                $sm = new CMSNavMaterial(false);
                $sm->MaterialID = $db_material->id;
                $sm->StructureID = $cmsnav_id;
                $sm->Active = 1;
                $sm->save();
            }

            // Success
            return array_merge( array( 'status' => TRUE ), $this->__async_form($db_material->id) );
        }

        // Fail
        return array_merge( array( 'status' => FALSE ) );
    }

    /**
     * Render materials table and pager
     * @param string $cmsnav 	Parent CMSNav identifier
     * @param string $search	Keywords to filter table
     * @param string $page		Current table page
     * @return array Collection of rendered table and pager data
     */
    function __async_table($cmsnav = null, $search = null, $page = null)
    {
        // Try to find cmsnav
        if (isset($cmsnav) && (is_object($cmsnav) || dbQuery('\samson\cms\Navigation')->id($cmsnav)->first($cmsnav))) {
            // Handle successfull found
        }

        // Generate materials table
        $table = new Table($cmsnav, $search, $page, $this);

        // Render table and pager
        return array('status' => 1, 'table_html' => $table->render(), 'pager_html' => $table->pager->toHTML());
    }

    /**
     * Publish/Unpublish material
     * @param mixed $_cmsmat Pointer to material object or material identifier
     * @return array Operation result data
     */
    function __async_publish( $_cmsmat )
    {
        // Get material safely
        if( cmsquery()->id($_cmsmat)->first( $cmsmat ) )
        {
            // Toggle material published status
            $cmsmat->Published = $cmsmat->Published ? 0 : 1;

            // Save changes to DB
            $cmsmat->save();

            // Действие не выполнено
            return array( 'status' => TRUE );
        }
        // Return error array
        else return array( 'status' => FALSE, 'message' => 'Material "'.$_cmsmat.'" not found');
    }

    /**
     * Delete material
     * @param mixed $_cmsmat Pointer to material object or material identifier
     * @return array Operation result data
     */
    function __async_remove( $_cmsmat )
    {
        // Get material safely
        if( cmsquery()->id($_cmsmat)->first( $cmsmat ) )
        {
            // Mark material as deleted
            $cmsmat->Active = 0;

            // Save changes to DB
            $cmsmat->save();

            // Действие не выполнено
            return array( 'status' => TRUE );
        }
        // Return error array
        else return array( 'status' => FALSE, 'message' => 'Material "'.$_cmsmat.'" not found');
    }

// 	/**
// 	 * Copy material
// 	 * @param mixed $_cmsmat Pointer to material object or material identifier
// 	 * @return array Operation result data
// 	 */
// 	function __async_copy( $_cmsmat )
// 	{
// 		return array( 'status' => FALSE, 'message' => 'Material "'.$_cmsmat.'" not found');

// 		// Get material safely 
// 		if( cmsquery()->id($_cmsmat)->first( $cmsmat ) )
// 		{
// 			// Toggle material published status
// 			$cmsmat->Published = $cmsmat->Published ? 0 : 1;

// 			// Save changes to DB
// 			$cmsmat->save();

// 			// Действие не выполнено
// 			return array( 'status' => TRUE );
// 		}		
// 		// Return error array
// 		else return array( 'status' => FALSE, 'message' => 'Material "'.$_cmsmat.'" not found');
// 	}

    /** Output for main page */
    public function main()
    {
        // Получим все материалы
        if( dbQuery('samson\cms\cmsmaterial')->join('user')->Active(1)->Draft(0)->order_by('Created','DESC')->limit(5)->exec($db_materials) )
        {
            // Render material rows
            $rows_html = '';
            foreach ( $db_materials as $db_material ) $rows_html .= $this->view('main/row')
                ->material($db_material)
                ->user($db_material->onetoone['_user'])
                ->output();

            for ($i = sizeof($db_materials); $i < 5; $i++)
            {
                $rows_html .= $this->view('main/row')->output();
            }

            // Render main template
            return $this->view('main/index')->rows( $rows_html )->output();
        }
    }
}
