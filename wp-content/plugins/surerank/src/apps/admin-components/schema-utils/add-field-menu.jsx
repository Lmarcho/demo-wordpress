import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Select } from '@bsf/force-ui';
import { Plus } from 'lucide-react';

/**
 * Renders a searchable select component for adding fields to a schema
 *
 * @param {Object}   props                 - Component props
 * @param {Array}    props.availableFields - Array of field objects that can be added
 * @param {Function} props.onAddField      - Callback function when a field is selected
 * @param {string}   props.className       - Optional CSS classes for the wrapper div
 * @return {JSX.Element|null} The AddFieldMenu component or null if no fields available
 */
export const AddFieldMenu = ( {
	availableFields,
	onAddField,
	className = 'p-2 w-full border-t border-border-subtle',
} ) => {
	const [ showFieldSelector, setShowFieldSelector ] = useState( false );

	if ( ! availableFields || availableFields.length === 0 ) {
		return null;
	}

	const handleFieldSelection = ( value ) => {
		if ( value ) {
			onAddField( value );
			setShowFieldSelector( false );
		}
	};

	return (
		<div className={ className }>
			{ ! showFieldSelector ? (
				<Button
					variant="outline"
					size="md"
					icon={ <Plus className="size-4" /> }
					iconPosition="left"
					onClick={ () => setShowFieldSelector( true ) }
				>
					{ __( 'Add Field', 'surerank' ) }
				</Button>
			) : (
				<Select
					value=""
					onChange={ handleFieldSelection }
					combobox
					size="md"
					open={ showFieldSelector }
					onOpenChange={ setShowFieldSelector }
				>
					<Select.Button
						label={ __( 'Add Field', 'surerank' ) }
						placeholder={ __( 'Search fields…', 'surerank' ) }
					/>
					<Select.Options className="z-[99999]">
						{ availableFields.map( ( field ) => (
							<Select.Option key={ field.id } value={ field.id }>
								{ field.label }
							</Select.Option>
						) ) }
					</Select.Options>
				</Select>
			) }
		</div>
	);
};
