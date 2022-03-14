<div class="page-bar">
    <ul class="page-breadcrumb">
        <li>
            <i class="fa fa-home"></i>
            <a href="<?= $this->Url->build(('/Dashboard'), true); ?>"><?= __('Dashboard') ?></a>
            <i class="fa fa-angle-right"></i>
        </li>
        <li><?= $this->Html->link(__('Factory Product Returns'), ['action' => 'index']) ?></li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="portlet box grey-cascade">
            <div class="portlet-title">
                <div class="caption">
                    <i class="fa fa-list-alt fa-lg"></i><?= __('Factory Product Returns List') ?>
                </div>
                <?php if($addPermission == 1){ ?>
                <div class="tools">
                    <?= $this->Html->link(__('New Product Return'), ['action' => 'add'], ['class' => 'btn btn-sm grey-gallery']); ?>
                </div>
                <?php } ?>
            </div>
            <div class="portlet-body">
                <div id="factoryProductReturnTableDiv" class="table-scrollable">
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">

    $(document).ready(function () {
        var source =
            {
                localdata: <?php echo json_encode($factoryProductReturnArr); ?>,
                datafields:
                    [
                        {name: 'id', type: 'number'},
                        {name: 'from_warehouse_name', type: 'string'},
                        {name: 'to_warehouse_name', type: 'string'},
                        {name: 'date', type: 'string'},
                        {name: 'chalan_no', type: 'string'},
                        {name: 'approve_status', type: 'number'},
                        {name: 'status', type: 'number'},
                        {name: 'edit_delete_permission', type: 'number'}
                    ],
                datatype: "json"
            };

        var dataAdapter = new $.jqx.dataAdapter(source);
        var columnsrenderer = function (value) {
            return '<div style="text-align: center; margin-top: 5px;">' + value + '</div>';
        };

        $("#factoryProductReturnTableDiv").jqxGrid(
            {
                width: '100%',
                source: dataAdapter,
                pageable: true,
                autorowheight: true,
                autoheight: true,
                altrows: true,
                showfilterrow: true,
                filterable: true,
                pagesizeoptions: ['20', '50', '100'],
                pagesize: 20,
                columns: [
                    {
                        text: 'SL No.', datafield: '', columnType: 'number', width: '5%', filterable: false,renderer: columnsrenderer,
                        cellsrenderer: function (row, column, value) {
                            return "<div style='margin:4px;'>" + (value + 1) + "</div>";
                        }
                    },
                    {text: 'From Warehouse', datafield: 'from_warehouse_name', width: '12%', renderer: columnsrenderer},
                    {text: 'To Warehouse', datafield: 'to_warehouse_name', width: '16%', renderer: columnsrenderer},
                    {text: 'Date', datafield: 'date', width: '10%', renderer: columnsrenderer},
                    {text: 'Chalan No', datafield: 'chalan_no', width: '18%', renderer: columnsrenderer},
                    {
                        text: 'Status', datafield: 'approve_status', width: '8%', renderer: columnsrenderer,
                        cellsrenderer: function (row, column, value) {
                            if(value == 'Pending'){
                                return "<div style='padding:10px 5px 10px 5px;'>" +
                                    "<span class='label label-default'>" + value + "</span>" +
                                    "</div>";
                            }else if(value == 'Approved'){
                                return "<div style='padding:10px 5px 10px 5px;'>" +
                                    "<span class='label label-warning'>" + value + "</span>" +
                                    "</div>";
                            }else if(value == 'Received'){
                                return "<div style='padding:10px 5px 10px 5px;'>" +
                                    "<span class='label label-success'>" + value + "</span>" +
                                    "</div>";
                            }
                        }
                    },
                    {
                        text: 'Actions', datafield: 'id', width: '30%', filterable: false, renderer: columnsrenderer,
                        cellsrenderer: function (row, column, value, t1, t2, t3) {

                            var output = "<div style='padding:5px 5px 0 5px;'>";
                            if(<?=$userGroupId?> == 11 && t3.approve_status != 'Approved' && t3.approve_status != 'Received'){
                                output += "<a href='<?php echo $this->Url->build(['action' => 'approve']); ?>/" + value + "' class='btn btn-sm btn-success' style='margin-right:5px;'>Approve</a>";
                            }
                            output += "<a href='<?php echo $this->Url->build(['action' => 'view']); ?>/" + value + "' class='btn btn-sm btn-info' style='margin-right:5px;'>View</a>";
                            if(t3.edit_delete_permission == 1){
                                if(<?=$userGroupId?> == 11){
                                    output += "<a href='<?php echo $this->Url->build(['action' => 'Edit']); ?>/" + value + "' class='btn btn-sm btn-warning' style='margin-right:5px;'>Edit</a>" +
                                        "<a href='<?php echo $this->Url->build(['action' => 'Delete']); ?>/" + value + "' class='confirmDelete btn btn-sm btn-danger' style='margin-right:5px;'>Delete</a>";
                                }else if(t3.approve_status != 'Approved' && t3.approve_status != 'Received') {
                                    output += "<a href='<?php echo $this->Url->build(['action' => 'Edit']); ?>/" + value + "' class='btn btn-sm btn-warning' style='margin-right:5px;'>Edit</a>" +
                                        "<a href='<?php echo $this->Url->build(['action' => 'Delete']); ?>/" + value + "' class='confirmDelete btn btn-sm btn-danger' style='margin-right:5px;'>Delete</a>";
                                }
                            }
                            else{

                                if(t3.approve_status == 'Received'){
                                    output += "<a href='<?php echo $this->Url->build(['action' => 'decideView']); ?>/" + value + "' class='btn btn-sm btn-success' style='margin-right:5px;'>Decide</a>";
                                }
                                if(t3.approve_status == 'Approved'){
                                    output += "<a href='<?php echo $this->Url->build(['action' => 'receive']); ?>/" + value + "' class='btn btn-sm btn-success' style='margin-right:5px;'>Receive</a>";
                                }
                            }
                            output += "</div>";
                            return output;
                        }
                    }
                ]
            });

        $(document).on('click', '.confirmDelete', function (e) {
            var d = confirm('Are you sure to delete?');
            if (!d)
                e.preventDefault();
        })
    });
</script>

