import { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
         <svg {...props} viewBox="0 0 100 104" xmlns="http://www.w3.org/2000/svg">
            <image href="/logo.svg" x="0" y="0" width="100" height="104" />
        </svg>

    );
}
