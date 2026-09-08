import cutleryLogo from '../../../database/seeders/images/cutlery.png';

export default function CanteenLogo({ alt = 'FYP Canteen', ...props }) {
    return <img {...props} src={cutleryLogo} alt={alt} />;
}
