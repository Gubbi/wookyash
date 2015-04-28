<?php if(count($payments) == 0):?>
	<div class="notice">No shops available</div>
<?php else:?>
    <table width="100%" border="0" cellspacing="0" cellpadding="0" id="payment_points" class="data-table">
    	<colgroup>
            <col style="width:200px;">
            <col>
        </colgroup>
        <thead>
            <tr>
                <th style="text-align:left;height:30px; border-bottom:1px solid #ccc">Shop Name</th>
                <th style="text-align:left;height:30px; border-bottom:1px solid #ccc">Address</th>
            </tr>
        </thead>
        <tbody>
        <?php $index = 1; ?>
		<?php foreach($payments as $payment):?>
            <tr class="<?php echo ((($index++)%2) == 0 ? "even" : "odd")?>">
                <td  style="height:30px"><?php echo $payment['shop_name']?></td>
                <td><?php echo $payment['address']?></td>
            </tr>
        <?php endforeach;?>
        <tbody>
    </table>
<?php endif;?>