<?php
use Cake\Core\Configure;
$webroot =  $this->request->webroot;
$productStatus = Configure::read('factory_product_return_product_status');
$approveStatus = Configure::read('factory_product_return_approve_status');
?>
<div class="page-bar">
    <ul class="page-breadcrumb">
        <li>
            <i class="fa fa-home"></i>
            <a href="<?= $this->Url->build(('/Dashboard'), true); ?>"><?= __('Dashboard') ?></a>
            <i class="fa fa-angle-right"></i>
        </li>
        <li>
            <?= $this->Html->link(__('Factory Product Returns'), ['action' => 'index']) ?>
            <i class="fa fa-angle-right"></i>
        </li>
        <li>
            <span>Factory Product Returns Chalan Receipt</span>
        </li>
    </ul>
</div>

<div style="margin: -7px 0 0 0px; padding: 43px; background-color: #ffffff; width: 100%">
    <div class="portlet-body">
        <div style="margin:0">
            <button class="btn btn-circle red icon-print2" style="float: right;" onclick="print_rpt(<?=$webroot?>)">&nbsp;Print&nbsp;</button>
            <?php
            if($approveStatus[$factoryProductReturns->approve_status] == 'Pending' && $userGroupId == 11){
                echo $this->Html->link(__('Approve'), ['action' => 'approve', $factoryProductReturns->id], ['id' => 'approveBtn', 'class' => 'btn btn-circle default yellow-stripe', 'data-toggle'=>"confirmation", 'data-original-title'=>"Are you sure to Receive Delivery ?"]);
            }else if($approveStatus[$factoryProductReturns->approve_status] == 'Approved' && $receivePermission == 1){
                echo $this->Html->link(__('Receive Delivery'), ['action' => 'receive', $factoryProductReturns->id], ['id' => 'receiveDeliveryBtn', 'class' => 'btn btn-circle default green-stripe', 'data-toggle'=>"confirmation", 'data-original-title'=>"Are you sure to Receive Delivery ?"]);
            }
            ?>
        </div>
        <div id="PrintArea" style="width: 100%;">
            <div>
                <table style="width: 100%; margin: 15px 30px 15px 0;">
                    <tr>
                        <td>
                            <div>
                                <h3>East West Chemicals Limited</h3>
                                <h5>Corporate Office: <br><br>52/1- New Eskaton Road Hasan Holdings Ltd (9th Floor), Dhaka 1000, Bangladesh.</h5>
                                <h5>Phone: 02-9360658, 8359881, Fax: 02-9351395.</h5>
                            </div>
                        </td>
                        <td>
                            <div style="text-align: right">
                                <h3>Factory Return Chalan</h3>
                                <h5>Date: <?= date('d-m-Y h:i:s', $factoryProductReturns->date)?></h5>
                            </div>
                            <div class="pull-right" style="text-align: right">
                                <h5>Chalan No: <?php echo $factoryProductReturns->chalan_no; ?></h5>
                                <h5>Chalan Status:
                                    <?php
                                    if($approveStatus[$factoryProductReturns->approve_status] == 'Pending'){
                                        ?><span class="label label-default" style="padding: 3px 8px;font-size: 14px;">Pending</span><?php
                                    }
                                    else if($approveStatus[$factoryProductReturns->approve_status] == 'Approved'){
                                        ?><span class="label label-warning" style="padding: 3px 8px;font-size: 14px;">Approved</span><?php
                                    }
                                    else if($approveStatus[$factoryProductReturns->approve_status] == 'Received'){
                                        ?><span class="label label-success" style="padding: 3px 8px;font-size: 14px;">Received</span><?php
                                    }
                                    ?>
                                </h5>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <div>
                <hr />
            </div>

            <div>
                <table style="width: 100%; margin: 15px 30px 15px 0;">
                    <tr>
                        <td style="padding-right: 20px;">
                            <div>
                                <h5 style="text-decoration: underline;">Delivery from:</h5>
                                <h5><?php echo $fromWarehouseUser->full_name_en." (".$fromWarehouseUser->user_group->title_en.")"; ?></h5>
                                <h5><?php echo $factoryProductReturns->from_warehouse->name; ?></h5>
                                <h5><?php echo $factoryProductReturns->from_warehouse->address; ?></h5>
                            </div>
                        </td>
                        <td style="padding-left: 30px;border-left: #ccc 1px solid;">
                            <div>
                                <h5 style="text-decoration: underline;">Delivery To:</h5>
                                <h5><?php echo $toWarehouseUser->full_name_en." (".$toWarehouseUser->user_group->title_en.")"; ?></h5>
                                <h5><?php echo $factoryProductReturns->to_warehouse->name; ?></h5>
                                <h5><?php echo $factoryProductReturns->to_warehouse->address; ?></h5>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

            <div>
                <hr />
            </div>

            <div>
                <table class="table table-hover table-bordered">
                    <tr>
                        <td colspan="5" style="text-align:center; font-size: 16px;font-weight: bold;">Factory Product Return Items</td>
                    </tr>
                    <tr>
                        <th>SN</th>
                        <th>Item</th>
                        <th>Unit</th>
                        <th>Product Status</th>
                        <th>Quantity</th>
                    </tr>
                    <?php
                    $totalQuantity = 0;
                    $i=1;
                    foreach($factoryProductReturns->factory_product_return_items as $item):
                        ?>
                        <tr>
                            <td><?php echo $i; ?></td>
                            <td><?= $item->item_unit->item_name;?></td>
                            <td><?= $item->item_unit->unit_display_name;?></td>
                            <td><?= $productStatus[$item->product_status];?></td>
                            <td>
                                <?php
                                $quantity=$item->quantity>0?$item->quantity:0;
                                echo $quantity;
                                $totalQuantity+=$quantity;
                                ?>
                            </td>
                        </tr>
                        <?php
                        $i++;
                    endforeach;
                    ?>
                    <tr>
                        <td colspan="4" style="text-align: right;font-weight: bold;">Total: </td>
                        <td style="font-weight: bold;"><?php echo $totalQuantity; ?></td>
                    </tr>
                </table>
            </div>


            <div style="margin-top: 150px;">
                <table class="table" style="border: 0;">
                    <tr>
                        <td style="text-decoration: overline;text-align: left">Sender Signature</td>
                        <td style="text-decoration: overline; text-align: right">Receiver Signature</td>
                    </tr>
                    <tr>
                        <td>
                            <div style="margin-top: 50px;">
                                <h5><?php echo $factoryProductReturns->from_warehouse->name; ?></h5>
                                <h5><?php echo $factoryProductReturns->from_warehouse->address; ?></h5>
                            </div>
                        </td>
                        <td>
                            <div style="margin-top: 50px;text-align:right;">
                                <h5><?php echo $factoryProductReturns->to_warehouse->name; ?></h5>
                                <h5><?php echo $factoryProductReturns->to_warehouse->address; ?></h5>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>

        </div>
    </div>
</div>

