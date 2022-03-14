<?php
$productStatus = Cake\Core\Configure::read(['factory_product_return_product_status']);
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
        <li><?= __('New Product Return') ?></li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="portlet box grey-cascade">
            <div class="portlet-title">
                <div class="caption">
                    <i class="fa fa-plus-square-o fa-lg"></i><?= __('New Product Return') ?>
                </div>
                <div class="tools">
                    <?= $this->Html->link(__('Back'), ['action' => 'index'], ['class' => 'btn btn-sm grey-gallery']); ?>
                </div>
            </div>

            <div class="portlet-body">
                <?= $this->Form->create($factoryProductReturns, ['id' => 'factoryProductReturnAddForm', 'class' => 'form-horizontal', 'role' => 'form']) ?>
                <div class="row">
                    <div class="col-md-7 col-md-offset-2">
                        <?php
                        echo $this->Form->input('from_warehouse', ['options'=>$warehouses, 'label'=>'Warehouse', 'class'=>'form-control level', 'empty'=>__('Select'), 'required'=>'required']);
                        ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="list" data-index_no="0">
                            <div class="itemWrapper">
                                <table class="table table-bordered moreTable">
                                    <tr>
                                        <th><?= __('Item')?></th>
                                        <th><?= __('Quantity')?></th>
                                        <th><?= __('Product Status')?></th>
                                        <th></th>
                                    </tr>
                                    <tr class="item_tr single_list">
                                        <td style="width: 50%;"><?php echo $this->Form->input('details.0.item_unit_id', ['options' => $itemUnitArr, 'required'=>'required', 'style'=>'max-width: 100%', 'class'=>'form-control item', 'empty' => __('Select'), 'templates'=>['label' => '']]);?></td>
                                        <td><?php echo $this->Form->input('details.0.quantity', ['type' => 'text', 'style'=>'width: 100%', 'required'=>'required', 'class'=>'form-control quantity numbersOnly', 'templates'=>['label' => '']]);?></td>
                                        <td><?php echo $this->Form->input('details.0.product_status', ['type' => 'select', 'options' => $productStatus, 'empty' => __('Select'), 'style'=>'width: 100%', 'required'=>'required', 'class'=>'form-control', 'templates'=>['label' => '']]);?></td>
                                        <td width="50px;"><span class="btn btn-sm btn-circle btn-danger remove pull-right">X</span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row col-md-offset-11">
                        <input type="button" class="btn btn-circle yellow add_more" value="Add" />
                    </div>

                    <div class="row text-center" style="margin-bottom: 20px;">
                        <?= $this->Form->button(__('Submit'), ['type'=>'submit', 'id' => 'factoryProductReturnAddSubmitBtn', 'class' => 'btn btn-circle default green-stripe forward_btn', 'name'=>'factoryProductReturnAddSubmitBtn', 'style' => 'margin-top:20px']) ?>
                    </div>
                </div>
                <?= $this->Form->end() ?>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){

        $(document).on("keyup", ".numbersOnly", function(event) {
            this.value = this.value.replace(/[^0-9\.]/g,'');
        });

        $(document).on('click', '.add_more', function () {
            var index = $('.list').data('index_no');
            $('.list').data('index_no', index + 1);
            var html = $('.itemWrapper .item_tr:last').clone().find('.form-control').each(function () {
                this.name = this.name.replace(/\d+/, index+1);
                this.id = this.id.replace(/\d+/, index+1);
                this.value = '';
            }).end();

            $('.moreTable').append(html);
        });

        $(document).on('click', '.remove', function () {
            var obj=$(this);
            var count= $('.single_list').length;
            if(count > 1) {
                obj.closest('.single_list').remove();
            }
        });

        // $(document).on('change', '.item', function() {
        //     var myArr = [];
        //     $( ".item" ).each(function( index ) {
        //         myArr.push($(this).val());
        //     });
        //
        //     var uniqueArr = uniqueArray(myArr);
        //
        //     if(myArr.length != uniqueArr.length) {
        //         toastr.error('Duplicate item not acceptable!');
        //         $(this).val('');
        //     }
        // });

        $(document).on('submit', '#factoryProductReturnAddForm', function(e){
            $("#factoryProductReturnAddSubmitBtn").attr("disabled", true);
        });
    });

    function uniqueArray(arr) {
        var i,
            len = arr.length,
            out = [],
            obj = { };

        for (i = 0; i < len; i++) {
            obj[arr[i]] = 0;
        }
        for (i in obj) {
            out.push(i);
        }
        return out;
    }
</script>
