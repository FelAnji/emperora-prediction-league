import { useEntityProp } from '@wordpress/core-data';
import { TextControl, SelectControl } from '@wordpress/components';
import { useBlockProps } from '@wordpress/block-editor';
import { useEffect, useState } from '@wordpress/element';

export default function Edit( { attributes, setAttributes } ) {
    const blockProps = useBlockProps();
    const [meta, setMeta] = useEntityProp('postType', 'epl_match', 'meta');
    const [rounds, setRounds] = useState([]);
	const [rawRounds, setRawRounds] = useState([]);

    useEffect(() => {
        fetch('/wp-json/emperora/v1/rounds', {
            headers: {
                'X-WP-Nonce': wpApiSettings.nonce
            }
        })
        .then(res => res.json())
        .then(data => {
			setRawRounds(data); // keep the original data
			const options = [
				{ label: 'Select a round', value: '' },
				...data.map(round => ({
					label: round.title,
					value: String(round.id)
				}))
			];
			setRounds(options);
		});
    }, []);

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
                    label={ `${meta?.home_team || 'Home'} score` }
                    value={meta?.home_score || ''}
                    onChange={ (newScore) => setMeta({ ...meta, home_score: newScore }) }
                />
                <TextControl 
                    label={ `${meta?.away_team || 'Away'} score` }
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
            <SelectControl
                label='Round'
                value={ String(meta?.round_id || '') }
                onChange={ (newRound) => {
				const selected = rawRounds.find(r => String(r.id) === newRound);
				setMeta({ 
					...meta, 
					round_id: parseInt(newRound),
					season_id: selected ? parseInt(selected.season_id) : null
				});
			}}
                options={ rounds }
            />
        </div>
    );	
}