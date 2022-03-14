<?php
namespace App\Controller;


use App\Controller\AppController;
use App\Model\Table\AdministrativeUnitsTable;
use App\View\Helper\SystemHelper;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\View\View;
use Exception;


/**
 * FactoryProductReturns Controller
 *
 */
class FactoryProductReturnsController extends AppController
{

//    public $paginate = [
//        'limit' => 15,
//        'order' => [
//            'Customers.id' => 'desc'
//        ]
//    ];

    /**
     * Index method
     *
     * @return void
     */
    public function index()
    {
        $user = $this->Auth->user();
        $userUnitId = $user['administrative_unit_id'];
        $userGroupId = $user['user_group_id'];

        $addPermission = $user['warehouse_id'] == 16 ? 0 : 1;

        if($userGroupId == 11  OR $userGroupId == 19 OR $userGroupId == 21 ){
            $factoryProductReturns = $this->FactoryProductReturns->find('all', [
                'contain' => ['FromWarehouses','ToWarehouses'],
                'conditions' => ['FactoryProductReturns.status !=' => 99],
                'order' => ['FactoryProductReturns.id' => 'desc']
            ]);
        }else{
            $factoryProductReturns = $this->FactoryProductReturns->find('all', [
                'contain' => ['FromWarehouses','ToWarehouses'],
                'conditions' => ['FactoryProductReturns.status !=' => 99,
                    'OR' => [
                        'FactoryProductReturns.from_warehouse_id'=>$user['warehouse_id'],
                        'FactoryProductReturns.to_warehouse_id'=>$user['warehouse_id']
                    ]
                ],
                'order' => ['FactoryProductReturns.id' => 'desc']
            ]);
        }
        $factoryProductReturnArr = array();
        foreach ($factoryProductReturns as $factoryProductReturn){
            $factoryProductReturnArr[] = array(
                "id" => $factoryProductReturn->id,
                "from_warehouse_name" => $factoryProductReturn->from_warehouse->name,
                "to_warehouse_name" => $factoryProductReturn->to_warehouse->name,
                "date" => date('d-m-Y', $factoryProductReturn->date),
                "chalan_no" => $factoryProductReturn->chalan_no,
                "approve_status" => Configure::read(['factory_product_return_approve_status'])[$factoryProductReturn->approve_status],
                "status" => $factoryProductReturn->status,
                'edit_delete_permission' => ($user['warehouse_id'] == $factoryProductReturn->to_warehouse->id) ? 0 : 1
            );
        }

        $this->set(compact('factoryProductReturnArr', 'userGroupId', 'addPermission'));
    }

    /**
     * View method
     *
     * @param string|null $id Customer id.
     * @return void
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function view($id = null)
    {
        $user = $this->Auth->user();
        $userGroupId = $user['user_group_id'];

        $this->loadModel('Users');

        $receivePermission = 0;

        $factoryProductReturns = $this->FactoryProductReturns->get($id, [
            'contain' => ['FromWarehouses', 'ToWarehouses', 'FactoryProductReturnItems'=>['ItemUnits']]
        ]);
        $fromWarehouseUser = $this->Users->find('all', ['contain' => ['UserGroups'], 'fields'=>['Users.full_name_en','UserGroups.title_en'], 'conditions'=>['warehouse_id'=>$factoryProductReturns->from_warehouse->id]])->first();
        $toWarehouseUser = $this->Users->find('all', ['contain' => ['UserGroups'], 'fields'=>['Users.full_name_en','UserGroups.title_en'], 'conditions'=>['warehouse_id'=>$factoryProductReturns->to_warehouse->id]])->first();

        if($user['warehouse_id'] == $factoryProductReturns->to_warehouse->id){
            $receivePermission = 1;
        }

        $this->set(compact('id', 'factoryProductReturns','userGroupId','fromWarehouseUser','toWarehouseUser', 'receivePermission'));
    }

    // new method for  decide products

    public function decideView($id = null)
    {
        $user = $this->Auth->user();
        $userGroupId = $user['user_group_id'];

        $this->loadModel('Users');

        $receivePermission = 0;

        $factoryProductReturns = $this->FactoryProductReturns->get($id, [
            'contain' => ['FromWarehouses', 'ToWarehouses', 'FactoryProductReturnItems'=>['ItemUnits']]
        ]);
        $fromWarehouseUser = $this->Users->find('all', ['contain' => ['UserGroups'], 'fields'=>['Users.full_name_en','UserGroups.title_en'], 'conditions'=>['warehouse_id'=>$factoryProductReturns->from_warehouse->id]])->first();
        $toWarehouseUser = $this->Users->find('all', ['contain' => ['UserGroups'], 'fields'=>['Users.full_name_en','UserGroups.title_en'], 'conditions'=>['warehouse_id'=>$factoryProductReturns->to_warehouse->id]])->first();

        if($user['warehouse_id'] == $factoryProductReturns->to_warehouse->id){
            $receivePermission = 1;
        }

        $this->set(compact('id', 'factoryProductReturns','userGroupId','fromWarehouseUser','toWarehouseUser', 'receivePermission'));
    }






    /**
     * Add method
     *
     * @return void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $user = $this->Auth->user();
        $userGroupId = $user['user_group_id'];
        $time = time();
        $this->loadModel('ItemUnits');
        $this->loadModel('Warehouses');
        $this->loadModel('Stocks');
        $this->loadModel('FactoryProductReturnItems');

        $factoryProductReturns = $this->FactoryProductReturns->newEntity();

        if ($this->request->is('post')) {
            try {
                $saveStatus = 0;
                $conn = ConnectionManager::get('default');
                $conn->transactional(function () use ($user, $time, &$saveStatus, $factoryProductReturns)
                {
                    $data = $this->request->data;
                    $fromWarehouse = $data['from_warehouse'];
                    $details = $data['details'];

                    //App::import('Helper', 'SystemHelper');
                    $SystemHelper = new SystemHelper(new View());
                    $chalanPrefix = "FPRC"; // FPRC = Factory Product Return Chalan
                    $chalanNo = $SystemHelper->generate_fpr_chalan_no($time, $user['administrative_unit_id'], $chalanPrefix);

                    /* to save FactoryProductReturns data */
                    $factoryProductReturnData['from_warehouse_id'] = $fromWarehouse;
                    $factoryProductReturnData['to_warehouse_id'] = 16;
                    $factoryProductReturnData['date'] = $time;
                    $factoryProductReturnData['chalan_no'] = $chalanNo;
                    $factoryProductReturnData['approve_status'] = 0;
                    $factoryProductReturnData['status'] = 1;
                    $factoryProductReturnData['created_by'] = $user['id'];
                    $factoryProductReturnData['created_date'] = $time;
                    $factoryProductReturns = $this->FactoryProductReturns->patchEntity($factoryProductReturns, $factoryProductReturnData);
                    if($result = $this->FactoryProductReturns->save($factoryProductReturns)){
                        $factoryProductReturnId = $result->id;
                        $factoryProductReturnItemData = array();
                        foreach ($details as $detail){
                            $itemUnitInfo = $this->ItemUnits->get($detail['item_unit_id']);
                            $factoryProductReturnItemData[] = array(
                                'factory_product_return_id' => $factoryProductReturnId,
                                'item_id' => $itemUnitInfo['item_id'],
                                'item_unit_id' => $detail['item_unit_id'],
                                'manufacture_unit_id' => $itemUnitInfo['manufacture_unit_id'],
                                'quantity' => $detail['quantity'],
                                'product_status' => $detail['product_status'],
                                'status' => 1,
                                'created_by' => $user['id'],
                                'created_date' => $time
                            );
                        }

                        $factoryProductReturnItems = $this->FactoryProductReturnItems->newEntities($factoryProductReturnItemData);
                        foreach($factoryProductReturnItems as $factoryProductReturnItem){
                            $this->FactoryProductReturnItems->save($factoryProductReturnItem);
                        }
                    }
                });

                $this->Flash->success('Factory Product Return successful. Thank you!');
                return $this->redirect(['action' => 'index']);
            } catch (Exception $e) {
//                echo '<pre>';
//                print_r($e);
//                echo '</pre>';
//                exit;
                $this->Flash->error('Problem to Return Factory Product. Please try again!');
                return $this->redirect(['action' => 'index']);
            }
        }

       // App::import('Helper', 'SystemHelper');
        $SystemHelper = new SystemHelper(new View());
        $itemUnitArr = $SystemHelper->get_item_unit_array();

        if($userGroupId == 11){
            $warehouses = $this->Warehouses->find('list', ['conditions' => ['status' => 1, "name NOT LIKE"=>"Factory%"]]);
        }else{
            $warehouses = $this->Warehouses->find('list', ['conditions' => ['id'=>$user['warehouse_id'], 'status' => 1, "name NOT LIKE"=>"Factory%"]]);
        }

        $this->set(compact('factoryProductReturns', 'warehouses', 'itemUnitArr'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Customer id.
     * @return void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $user = $this->Auth->user();
        $time = time();
        $this->loadModel('ItemUnits');
        $this->loadModel('Warehouses');
        $this->loadModel('Stocks');
        $this->loadModel('FactoryProductReturnItems');

        $factoryProductReturns = $this->FactoryProductReturns->get($id, ['contain' => ['FactoryProductReturnItems']]);

        if ($this->request->is(['put'])) {
            try {
                $saveStatus = 0;
                $conn = ConnectionManager::get('default');
                $conn->transactional(function () use ($id, $user, $time, &$saveStatus, $factoryProductReturns)
                {
                    $data = $this->request->data;
                    $fromWarehouse = $data['from_warehouse'];
                    $details = $data['details'];

                    $factoryProductReturns = $this->FactoryProductReturns->get($id);
                    $prevFromWarehouse = $factoryProductReturns->from_warehouse_id;
                    $prevToWarehouse = 14;

                    /* to save FactoryProductReturns data */
                    $factoryProductReturnData['from_warehouse_id'] = $fromWarehouse;
                    $factoryProductReturnData['to_warehouse_id'] = 16;
                    $factoryProductReturnData['date'] = $time;
                    $factoryProductReturnData['status'] = 1;
                    $factoryProductReturnData['updated_by'] = $user['id'];
                    $factoryProductReturnData['updated_date'] = $time;
                    $factoryProductReturns = $this->FactoryProductReturns->patchEntity($factoryProductReturns, $factoryProductReturnData);
                    if($result = $this->FactoryProductReturns->save($factoryProductReturns)){
//                        $factoryProductReturnId = $result->id;

                        /* updating warehouse's previous data */
                        $factoryProductReturnItems = $this->FactoryProductReturnItems->find('all', ['conditions' => ['factory_product_return_id' => $id]]);
                        foreach ($factoryProductReturnItems as $factoryProductReturnItem){
                            $fromWarehouseStock = $this->Stocks->find('all', ['conditions' => ['warehouse_id'=>$prevFromWarehouse, 'manufacture_unit_id'=>$factoryProductReturnItem->manufacture_unit_id, 'item_id'=>$factoryProductReturnItem->item_id]])->first();
                            $this->Stocks->query()->update()->set(['quantity' => $fromWarehouseStock['quantity']+$factoryProductReturnItem->quantity])->where(['warehouse_id'=>$prevFromWarehouse, 'manufacture_unit_id'=>$factoryProductReturnItem->manufacture_unit_id, 'item_id'=>$factoryProductReturnItem->item_id])->execute();

                            $toWarehouseStock = $this->Stocks->find('all', ['conditions' => ['warehouse_id'=>16, 'manufacture_unit_id'=>$factoryProductReturnItem->manufacture_unit_id, 'item_id'=>$factoryProductReturnItem->item_id]])->first();
                            $this->Stocks->query()->update()->set(['quantity' => $toWarehouseStock['quantity']-$factoryProductReturnItem->quantity])->where(['warehouse_id'=>16, 'manufacture_unit_id'=>$factoryProductReturnItem->manufacture_unit_id, 'item_id'=>$factoryProductReturnItem->item_id])->execute();
                        }

                        /* deleting FactoryProductReturnItems data of specific FactoryProductReturns id */
                        $this->FactoryProductReturnItems->deleteAll(["factory_product_return_id"=>$id]);


                        $factoryProductReturnItemData = array();
                        foreach ($details as $detail){
                            $itemUnitInfo = $this->ItemUnits->get($detail['item_unit_id']);
                            $factoryProductReturnItemData[] = array(
                                'factory_product_return_id' => $id,
                                'item_id' => $itemUnitInfo['item_id'],
                                'item_unit_id' => $detail['item_unit_id'],
                                'manufacture_unit_id' => $itemUnitInfo['manufacture_unit_id'],
                                'quantity' => $detail['quantity'],
                                'product_status' => $detail['product_status'],
                                'status' => 1,
                                'created_by' => $user['id'],
                                'created_date' => $time
                             );
                        }

                            $factoryProductReturnItems = $this->FactoryProductReturnItems->newEntities($factoryProductReturnItemData);
                            foreach($factoryProductReturnItems as $factoryProductReturnItem){
                                $fromWarehouseStock = $this->Stocks->find('all', ['conditions' => ['warehouse_id'=>$fromWarehouse, 'manufacture_unit_id'=>$factoryProductReturnItem->manufacture_unit_id, 'item_id'=>$factoryProductReturnItem->item_id]])->first();
                                $this->Stocks->query()->update()->set(['quantity' => $fromWarehouseStock['quantity']-$factoryProductReturnItem->quantity])->where(['warehouse_id'=>$fromWarehouse, 'manufacture_unit_id'=>$factoryProductReturnItem->manufacture_unit_id, 'item_id'=>$factoryProductReturnItem->item_id])->execute();

                                $toWarehouseStock = $this->Stocks->find('all', ['conditions' => ['warehouse_id'=>16, 'manufacture_unit_id'=>$factoryProductReturnItem->manufacture_unit_id, 'item_id'=>$factoryProductReturnItem->item_id]])->first();
                                $this->Stocks->query()->update()->set(['quantity' => $toWarehouseStock['quantity']+$factoryProductReturnItem->quantity])->where(['warehouse_id'=>16, 'manufacture_unit_id'=>$factoryProductReturnItem->manufacture_unit_id, 'item_id'=>$factoryProductReturnItem->item_id])->execute();

                                $this->FactoryProductReturnItems->save($factoryProductReturnItem);
                            }
                        }
                });

                    $this->Flash->success('Factory Product Return successful. Thank you!');
                    return $this->redirect(['action' => 'index']);
                } catch (Exception $e) {
    //                echo '<pre>';
    //                print_r($e);
    //                echo '</pre>';
    //                exit;
                    $this->Flash->error('Problem to Return Factory Product. Please try again!');
                    return $this->redirect(['action' => 'index']);
                }
        }


       // App::import('Helper', 'SystemHelper');
        $SystemHelper = new SystemHelper(new View());
        $itemUnitArr = $SystemHelper->get_item_unit_array();

        $warehouses = $this->Warehouses->find('list', ['conditions' => ['status' => 1, "name NOT LIKE"=>"Factory%"]]);

        $this->set(compact('factoryProductReturns', 'warehouses', 'itemUnitArr'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Customer id.
     * @return void Redirects to index.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $user = $this->Auth->user();
        $time = time();
        $this->loadModel('Stocks');
        $this->loadModel('FactoryProductReturnItems');

        $factoryProductReturns = $this->FactoryProductReturns->get($id);

        try {
            $saveStatus = 0;
            $conn = ConnectionManager::get('default');
            $conn->transactional(function () use ($id, $user, $time, &$saveStatus, $factoryProductReturns)
            {
                $prevFromWarehouse = $factoryProductReturns->from_warehouse_id;
                $prevToWarehouse = 14;

                $factoryProductReturnsData['updated_by'] = $user['id'];
                $factoryProductReturnsData['updated_date'] = time();
                $factoryProductReturnsData['status'] = 99;
                $factoryProductReturns = $this->FactoryProductReturns->patchEntity($factoryProductReturns, $factoryProductReturnsData);
                if ($this->FactoryProductReturns->save($factoryProductReturns)) {
                    /* updating warehouse's previous data */
                    $factoryProductReturnItems = $this->FactoryProductReturnItems->find('all', ['conditions' => ['factory_product_return_id' => $id]]);
                    foreach ($factoryProductReturnItems as $factoryProductReturnItem){
                        $fromWarehouseStock = $this->Stocks->find('all', ['conditions' => ['warehouse_id'=>$prevFromWarehouse, 'manufacture_unit_id'=>$factoryProductReturnItem->manufacture_unit_id, 'item_id'=>$factoryProductReturnItem->item_id]])->first();
                        $this->Stocks->query()->update()->set(['quantity' => $fromWarehouseStock['quantity']+$factoryProductReturnItem->quantity])->where(['warehouse_id'=>$prevFromWarehouse, 'manufacture_unit_id'=>$factoryProductReturnItem->manufacture_unit_id, 'item_id'=>$factoryProductReturnItem->item_id])->execute();

                        $toWarehouseStock = $this->Stocks->find('all', ['conditions' => ['warehouse_id'=>16, 'manufacture_unit_id'=>$factoryProductReturnItem->manufacture_unit_id, 'item_id'=>$factoryProductReturnItem->item_id]])->first();
                        $this->Stocks->query()->update()->set(['quantity' => $toWarehouseStock['quantity']-$factoryProductReturnItem->quantity])->where(['warehouse_id'=>16, 'manufacture_unit_id'=>$factoryProductReturnItem->manufacture_unit_id, 'item_id'=>$factoryProductReturnItem->item_id])->execute();

                        $factoryProductReturnItemsData['status'] = 99;
                        $factoryProductReturnItem = $this->FactoryProductReturnItems->patchEntity($factoryProductReturnItem, $factoryProductReturnItemsData);
                        $this->FactoryProductReturnItems->save($factoryProductReturnItem);
                    }
                }
            });

            $this->Flash->success('Factory Product Return data deleted successfully.');
            return $this->redirect(['action' => 'index']);
        } catch (Exception $e) {
//                echo '<pre>';
//                print_r($e);
//                echo '</pre>';
//                exit;
            $this->Flash->error('Problem to delete Factory Product Return data. Please try again!');
            return $this->redirect(['action' => 'index']);
        }
    }


    public function approve($id){
        $user = $this->Auth->user();
        $time = time();
        $this->loadModel('Stocks');
        $this->loadModel('FactoryProductReturnItems');

        $factoryProductReturns = $this->FactoryProductReturns->get($id);

        try {
            $saveStatus = 0;
            $conn = ConnectionManager::get('default');
            $conn->transactional(function () use ($id, $user, $time, &$saveStatus, $factoryProductReturns)
            {
                /* to save FactoryProductReturns data */
                $factoryProductReturnData['approve_status'] = 1;
                $factoryProductReturnData['updated_by'] = $user['id'];
                $factoryProductReturnData['updated_date'] = $time;
                $factoryProductReturns = $this->FactoryProductReturns->patchEntity($factoryProductReturns, $factoryProductReturnData);
                $this->FactoryProductReturns->save($factoryProductReturns);
            });

            $this->Flash->success('Factory Product Return approved successful. Thank you!');
            return $this->redirect(['action' => 'index']);
        } catch (Exception $e) {
//                echo '<pre>';
//                print_r($e);
//                echo '</pre>';
//                exit;
            $this->Flash->error('Problem to approve Return Factory Product. Please try again!');
            return $this->redirect(['action' => 'index']);
        }
    }


    public function receive($id){
        $user = $this->Auth->user();
        $time = time();
        $this->loadModel('Stocks');
        $this->loadModel('FactoryProductReturnItems');

        $factoryProductReturns = $this->FactoryProductReturns->get($id);

        try {
            $saveStatus = 0;
            $conn = ConnectionManager::get('default');
            $conn->transactional(function () use ($id, $user, $time, &$saveStatus, $factoryProductReturns)
            {
                /* to save FactoryProductReturns data */
                $factoryProductReturnData['approve_status'] = 2;
                $factoryProductReturnData['updated_by'] = $user['id'];
                $factoryProductReturnData['updated_date'] = $time;
                $factoryProductReturns = $this->FactoryProductReturns->patchEntity($factoryProductReturns, $factoryProductReturnData);
                if($this->FactoryProductReturns->save($factoryProductReturns)){

                    $factoryProductReturnItems = $this->FactoryProductReturnItems->find('all', ['conditions' => ['factory_product_return_id' => $id]]);
                    foreach ($factoryProductReturnItems as $factoryProductReturnItem){
                        $fromWarehouseStock = $this->Stocks->find('all', ['conditions' => ['warehouse_id'=>$factoryProductReturns->from_warehouse_id, 'manufacture_unit_id'=>$factoryProductReturnItem->manufacture_unit_id, 'item_id'=>$factoryProductReturnItem->item_id]])->first();
                        $this->Stocks->query()->update()->set(['quantity' => $fromWarehouseStock['quantity']-$factoryProductReturnItem->quantity])->where(['warehouse_id'=>$factoryProductReturns->from_warehouse_id, 'manufacture_unit_id'=>$factoryProductReturnItem->manufacture_unit_id, 'item_id'=>$factoryProductReturnItem->item_id])->execute();

                        $toWarehouseStock = $this->Stocks->find('all', ['conditions' => ['warehouse_id'=>16, 'manufacture_unit_id'=>$factoryProductReturnItem->manufacture_unit_id, 'item_id'=>$factoryProductReturnItem->item_id]])->first();
                      
                      
                    //   if($toWarehouseStock['quantity']>0){
                    //     print_r('jahid');
                    //   }elseif($toWarehouseStock['quantity']==0){
                    //     print_r('Hossain');
                    //   }
                    //   elseif($toWarehouseStock['quantity']==" "){
                    //     print_r('sumon');
                    // }
                    // elseif($toWarehouseStock['quantity']==''){
                    //     print_r('samir');
                    // }
                    //   die();
                      
                        if($toWarehouseStock['quantity']==0){
                            $stocks = $this->Stocks->newEntity();
                            $factorydumpProductReturnData['quantity'] = $factoryProductReturnItem->quantity;
                            $factorydumpProductReturnData['warehouse_id'] = 16;
                            $factorydumpProductReturnData['manufacture_unit_id'] = $factoryProductReturnItem->manufacture_unit_id;
                            $factorydumpProductReturnData['item_id'] = $factoryProductReturnItem->item_id;
                            // print_r($factorydumpProductReturnData);
                            // die();
                           
                            $stocks = $this->Stocks->patchEntity($stocks, $factorydumpProductReturnData);
                            $this->Stocks->save($stocks);
                         }
                       else{
                             $this->Stocks->query()->update()->set(['quantity' => $toWarehouseStock['quantity']+$factoryProductReturnItem->quantity])->where(['warehouse_id'=>16, 'manufacture_unit_id'=>$factoryProductReturnItem->manufacture_unit_id, 'item_id'=>$factoryProductReturnItem->item_id])->execute();
                         }
                       
                       
                        
                        $this->FactoryProductReturnItems->save($factoryProductReturnItem);
                    }
                }
            });

            $this->Flash->success('Factory Product Return approved successful. Thank you!');
            return $this->redirect(['action' => 'index']);
        } catch (Exception $e) {
//                echo '<pre>';
//                print_r($e);
//                echo '</pre>';
//                exit;
            $this->Flash->error('Problem to approve Return Factory Product. Please try again!');
            return $this->redirect(['action' => 'index']);
        }
    }
}
