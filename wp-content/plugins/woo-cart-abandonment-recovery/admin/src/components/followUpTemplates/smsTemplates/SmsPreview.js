import { Table, Input, Button, Switch } from '@bsf/force-ui';
import {
	TrashIcon,
	MagnifyingGlassIcon,
	PlusIcon,
	PencilIcon,
	DocumentDuplicateIcon,
} from '@heroicons/react/24/outline';
import { __ } from '@wordpress/i18n';

import { ProUpgradeCta } from '@Components/pro';
import TemplatesNav from '../TemplatesNav';

const SmsPreview = () => {
	// Temporary mock data for table preview
	const data = [
		{
			id: 'sms-1',
			template_name: 'Welcome Back Reminder',
			sms_frequency: 15,
			sms_frequency_unit: 'MINUTE',
		},
		{
			id: 'sms-2',
			template_name: 'First Follow-up',
			sms_frequency: 2,
			sms_frequency_unit: 'HOUR',
		},
		{
			id: 'sms-3',
			template_name: '24h Nudge',
			sms_frequency: 1,
			sms_frequency_unit: 'DAY',
		},
		{
			id: 'sms-4',
			template_name: 'Last Chance Offer',
			sms_frequency: 3,
			sms_frequency_unit: 'DAY',
		},
		{
			id: 'sms-5',
			template_name: 'Cart Value Upsell',
			sms_frequency: 6,
			sms_frequency_unit: 'HOUR',
		},
		{
			id: 'sms-6',
			template_name: 'Free Shipping Push',
			sms_frequency: 12,
			sms_frequency_unit: 'HOUR',
		},
		{
			id: 'sms-7',
			template_name: 'Weekend Deal Teaser',
			sms_frequency: 2,
			sms_frequency_unit: 'DAY',
		},
		{
			id: 'sms-8',
			template_name: 'VIP Customer Ping',
			sms_frequency: 30,
			sms_frequency_unit: 'MINUTE',
		},
		{
			id: 'sms-9',
			template_name: 'Low Stock Alert',
			sms_frequency: 45,
			sms_frequency_unit: 'MINUTE',
		},
		{
			id: 'sms-10',
			template_name: 'Bundle Reminder',
			sms_frequency: 4,
			sms_frequency_unit: 'HOUR',
		},
		{
			id: 'sms-11',
			template_name: 'Seasonal Promo',
			sms_frequency: 1,
			sms_frequency_unit: 'DAY',
		},
		{
			id: 'sms-12',
			template_name: 'Review Request',
			sms_frequency: 2,
			sms_frequency_unit: 'DAY',
		},
		{
			id: 'sms-13',
			template_name: 'Win-Back Offer',
			sms_frequency: 5,
			sms_frequency_unit: 'DAY',
		},
	];

	const formatDuration = ( value, unit ) => {
		const units = {
			MINUTE: 'Minutes',
			HOUR: 'Hours',
			DAY: 'Days',
		};
		unit = units[ unit ];
		const formattedUnit =
			parseInt( value ) === 1
				? unit.slice( 0, -1 ) // remove 's' for singular: "days" -> "day"
				: unit;

		return `${ value } ${ formattedUnit }`;
	};

	return (
		<div className="flex flex-col gap-4">
			<div className="flex flex-col md:flex-row gap-4 md:gap-0 justify-between relative">
				<TemplatesNav currentTab={ 'sms' } />
				<div className="flex flex-col md:flex-row gap-4">
					<Input
						placeholder={ __(
							'Search…',
							'woo-cart-abandonment-recovery'
						) }
						prefix={
							<MagnifyingGlassIcon className="h-6 w-6 text-gray-500" />
						}
						size="md"
						type="text"
						aria-label={ __(
							'Search',
							'woo-cart-abandonment-recovery'
						) }
						className="w-full lg:w-52"
						disabled={ true }
					/>
					<Button
						iconPosition="left"
						size="sm"
						tag="button"
						type="button"
						variant="outline"
						disabled={ true }
					>
						{ __(
							'Restore Default Templates',
							'woo-cart-abandonment-recovery'
						) }
					</Button>
					<Button
						className=""
						icon={ <PlusIcon aria-label="icon" role="img" /> }
						iconPosition="left"
						size="sm"
						tag="button"
						type="button"
						variant="primary"
						disabled={ true }
					>
						{ __(
							'Create New Template',
							'woo-cart-abandonment-recovery'
						) }
					</Button>
				</div>
			</div>
			<div className="relative">
				<Table
					checkboxSelection={ true }
					className="whitespace-nowrap sm:whitespace-normal"
				>
					<Table.Head>
						<Table.HeadCell>
							{ __(
								'Template Name',
								'woo-cart-abandonment-recovery'
							) }
						</Table.HeadCell>
						<Table.HeadCell>
							{ __(
								'Trigger After',
								'woo-cart-abandonment-recovery'
							) }
						</Table.HeadCell>
						<Table.HeadCell>
							{ __( 'Status', 'woo-cart-abandonment-recovery' ) }
						</Table.HeadCell>
						<Table.HeadCell className="text-right">
							{ __( 'Actions', 'woo-cart-abandonment-recovery' ) }
						</Table.HeadCell>
					</Table.Head>
					<Table.Body>
						{ data.map( ( item ) => (
							<Table.Row key={ item.id } value={ item }>
								<Table.Cell>
									<span className="cursor-pointer hover:text-flamingo-400 focus-visible:text-flamingo-400">
										{ item.template_name }
									</span>
								</Table.Cell>

								<Table.Cell>
									{ formatDuration(
										item.sms_frequency,
										item.sms_frequency_unit
									) }
								</Table.Cell>
								<Table.Cell>
									<Switch
										value={ true }
										size="md"
										className="border-none moderncart-toggle-field"
										role="switch"
									/>
								</Table.Cell>
								<Table.Cell>
									<div className="flex items-center justify-end gap-2">
										<Button
											variant="ghost"
											icon={
												<PencilIcon className="h-6 w-6" />
											}
											size="xs"
											className="text-gray-500 hover:text-flamingo-400"
											aria-label={ __(
												'Edit',
												'woo-cart-abandonment-recovery'
											) }
										/>
										<Button
											variant="ghost"
											icon={
												<DocumentDuplicateIcon className="h-6 w-6" />
											}
											size="xs"
											className="text-gray-500 hover:text-flamingo-400"
											aria-label={ __(
												'Duplicate',
												'woo-cart-abandonment-recovery'
											) }
										/>
										<Button
											variant="ghost"
											icon={
												<TrashIcon className="h-6 w-6" />
											}
											size="xs"
											className="text-gray-500 hover:text-red-600"
										/>
									</div>
								</Table.Cell>
							</Table.Row>
						) ) }
					</Table.Body>
				</Table>
				<ProUpgradeCta
					isVisible={ true }
					highlightText={ __(
						'Unlock Pro Features',
						'woo-cart-abandonment-recovery'
					) }
					mainTitle={ __(
						'Cart Abandonment Recovery Pro is Here 🔥',
						'woo-cart-abandonment-recovery'
					) }
					description={ __(
						"You've seen how emails bring shoppers back. With Pro, you'll unlock advanced tools that recover more carts, boost profits, and grow your store faster.",
						'woo-cart-abandonment-recovery'
					) }
					usps={ [
						__(
							'Product Reports',
							'woo-cart-abandonment-recovery'
						),
						__(
							'SMS + WhatsApp Followups',
							'woo-cart-abandonment-recovery'
						),
						__( 'Smart Rules', 'woo-cart-abandonment-recovery' ),
						__(
							'Advanced Automations',
							'woo-cart-abandonment-recovery'
						),
						__( 'And More…', 'woo-cart-abandonment-recovery' ),
					] }
					actionBtnUrlArgs={
						'utm_source=wcar-dashboard&utm_medium=free-wcar&utm_campaign=go-wcar-pro'
					}
					footerMessage={ '' }
					backgroundBlur={ true }
				/>
			</div>
		</div>
	);
};

export default SmsPreview;
