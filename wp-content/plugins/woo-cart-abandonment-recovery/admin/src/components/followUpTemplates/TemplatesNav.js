import { Tabs } from '@bsf/force-ui';
import { useNavigate } from 'react-router-dom';

const TemplatesNav = ( { currentTab } ) => {
	const navigate = useNavigate();
	return (
		<Tabs activeItem={ currentTab }>
			<Tabs.Group
				onChange={ ( { value } ) => {
					navigate( {
						search:
							'?page=woo-cart-abandonment-recovery&path=follow-up-templates&tab=' +
							value.slug,
					} );
				} }
				size="md"
				variant="rounded"
				width="auto"
				className="p-0 w-fit bg-white"
			>
				<Tabs.Tab
					slug="email"
					text="Email"
					className={ `${
						currentTab === 'email' &&
						'text-primary-600 bg-primary-25 outline outline-1 outline-primary-300 focus:outline-1'
					} hover:text-primary-600` }
				/>
				<Tabs.Tab
					slug="sms"
					text="SMS"
					className={ `${
						currentTab === 'sms' &&
						'text-primary-600 bg-primary-25 outline outline-1 outline-primary-300 focus:outline-1'
					} hover:text-primary-600` }
				/>
			</Tabs.Group>
		</Tabs>
	);
};

export default TemplatesNav;
