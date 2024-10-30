<?php
defined( 'ABSPATH' ) || exit;
?>
<td class="rule-type">
	<?php
	$types = apply_filters( 'xlwcfg_xlwcfg_rule_get_rule_types_product', array() );
	// create field
	$args = array(
		'input'   => 'select',
		'name'    => 'xlwcfg_rule[product][<%= groupId %>][<%= ruleId %>][rule_type]',
		'class'   => 'rule_type',
		'choices' => $types,
	);

	xlwcfg_Input_Builder::create_input_field( $args, 'html_always' );
	?>
</td>


<?php
XLWCFG_Core()->rules->render_rule_choice_template( array(
	'group_id'  => 0,
	'rule_id'   => 0,
	'rule_type' => 'all_products',
	'condition' => false,
	'operator'  => false,
	'category'  => 'product',
) );
?>
<td class="loading" colspan="2" style="display:none;"><?php _e( 'Loading...', RULE_TEXTDOMAIN ); ?></td>
<td class="add"><a href="#" class="xlwcfg-add-rule button"><?php _e( "AND", RULE_TEXTDOMAIN ); ?></a></td>
<td class="remove"><a href="#" class="xlwcfg-remove-rule xlwcfg-button-remove"></a></td>