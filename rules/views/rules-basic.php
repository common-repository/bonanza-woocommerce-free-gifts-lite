<?php
defined( 'ABSPATH' ) || exit;

$post_id = get_the_id();
$groups  = $this->get_entity_rules( $post_id );

if ( empty( $groups ) ) {
	$default_rule_id = 'rule' . uniqid();
	$groups          = array(
		'group' . ( time() ) => array(
			$default_rule_id => array(
				'rule_type' => 'general_always',
				'operator'  => '==',
				'condition' => '',
			),
		),

	);
}

?>
<div class="xlwcfg-rules-builder woocommerce_options_panel" data-category="basic">
    <div id="xlwcfg-rules-groups" class="xlwcfg_rules_common">
		<?php if ( is_array( $groups ) ) : ?>
            <div class="xlwcfg-rule-group-target">
				<?php
				$group_counter = 0;
				foreach ( $groups as $group_id => $group ) :
					if ( empty( $group_id ) ) {
						$group_id = 'group' . $group_id;
					}
					?>
                    <div class="xlwcfg-rule-group-container" data-groupid="<?php echo $group_id; ?>">
                        <div class="xlwcfg-rule-group-header">
							<?php if ( $group_counter > 0 ): ?>
                                <h4><?php _e( "or", 'bonanza-woocommerce-free-gifts-lite' ); ?></h4>
							<?php endif; ?>
                        </div>
						<?php
						if ( is_array( $group ) ) :
							?>
                            <table class="xlwcfg-rules" data-groupid="<?php echo $group_id; ?>">
                                <tbody>
								<?php
								foreach ( $group as $rule_id => $rule ) :
									if ( empty( $rule_id ) ) {
										$rule_id = 'rule' . $rule_id;
									}
									?>
                                <tr data-ruleid="<?php echo $rule_id; ?>" class="xlwcfg-rule">
                                    <td class="rule-type">
										<?php
										// allow custom location rules
										$types = apply_filters( 'xlwcfg_xlwcfg_rule_get_rule_types', array() );

										// create field
										$args = array(
											'input'   => 'select',
											'name'    => 'xlwcfg_rule[basic][' . $group_id . '][' . $rule_id . '][rule_type]',
											'class'   => 'rule_type',
											'choices' => $types,
										);
										xlwcfg_Input_Builder::create_input_field( $args, $rule['rule_type'] );
										?>
                                    </td>
									<?php
									$this->ajax_render_rule_choice( array(
										'group_id'      => $group_id,
										'rule_id'       => $rule_id,
										'rule_type'     => $rule['rule_type'],
										'condition'     => isset( $rule['condition'] ) ? $rule['condition'] : false,
										'operator'      => isset( $rule['operator'] ) ? $rule['operator'] : false,
										'rule_category' => 'basic',
									) );
									?>
                                    <td class="loading" colspan="2"
                                        style="display:none;"><?php _e( 'Loading...', RULE_TEXTDOMAIN ); ?></td>
                                    <td class="add">
                                        <a href="#"
                                           class="xlwcfg-add-rule button"><?php _e( 'AND', RULE_TEXTDOMAIN ); ?></a>
                                    </td>
                                    <td class="remove">
                                        <a href="#" class="xlwcfg-remove-rule xlwcfg-button-remove"
                                           title="<?php _e( 'Remove condition', RULE_TEXTDOMAIN ); ?>"></a>
                                    </td>
                                    </tr><?php endforeach; ?></tbody>
                            </table>
						<?php endif; ?>
                    </div>
					<?php $group_counter ++; ?>
				<?php endforeach; ?>
            </div>
            <h4 class="rules_or"
                style="<?php echo( $group_counter > 1 ? 'display:block;' : 'display:none' ); ?>"><?php _e( 'or when these conditions are matched', RULE_TEXTDOMAIN ); ?></h4>
            <button class="button button-primary xlwcfg-add-rule-group"
                    title="<?php _e( 'Add a set of conditions', RULE_TEXTDOMAIN ); ?>"><?php _e( 'OR', RULE_TEXTDOMAIN ); ?></button>
		<?php endif; ?>
        <div class="xlwcfg_rules_bottom_note">
			<?php
			_e( 'Unlock all the rules by switching to ', 'bonanza-woocommerce-free-gifts-lite' );
			?>
            <a href="javascript:void(0)" onclick="show_modal_pro('additional_rules_modal');" target="_blank"><?php _e( 'PRO version', 'bonanza-woocommerce-free-gifts-lite' ); ?>.</a>
        </div>
    </div>
</div>
<script type="text/template" id="xlwcfg-rule-template-basic">
	<?php include 'metabox-rules-rule-template-basic.php'; ?>
</script>