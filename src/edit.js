import { useEntityProp } from '@wordpress/core-data';
import { TextControl, NumberControl, SelectControl } from '@wordpress/components'
import { useBlockProps } from '@wordpress/block-editor';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();
	const [meta, setMeta] = useEntityProp('postType', 'epl_match', 'meta');

	// if ( ! meta ) return "Hi, I'm not available";

	return (
		<div { ...blockProps }>
			<TextControl 
				label='Home Team'
				value={meta?.home_team || ''}
				onChange={ (newValue) => setMeta({ ...meta, home_team: newValue}) }
				__next40pxDefaultSize
	__nextHasNoMarginBottom
			/>
			
			<TextControl 
				label='Away Team'
				value={meta?.away_team || ''}
				onChange={ (newValue) => setMeta({ ...meta, away_team: newValue}) }
				__next40pxDefaultSize
	__nextHasNoMarginBottom
			/>

			<div>
				<TextControl 
					label= {`${meta.home_team + ' score'}`}
					value={meta?.home_score || ''}
					onChange={ (newScore) => setMeta({ ...meta, home_score: newScore }) }
				/>
				<TextControl 
					label= {`${meta.away_team + ' score'}`}
					value={meta?.away_score || ''}
					onChange={ (newScore) => setMeta( { ...meta, away_score: newScore })}
				/>
			</div>

			<SelectControl 
				label='Match Status'
				value={meta?.match_status}
				onChange={(newStatus) => setMeta({...meta, match_status: newStatus}) }
				options={[
					{ label: 'Upcoming', value: 'upcoming' },
    				{ label: 'Completed', value: 'completed' },
				]}
			/>
		</div>

		
	);	
}