import {ChevronDown, type LucideIcon} from 'lucide-react';
import type {ReactNode, SyntheticEvent} from 'react';
import './settings-sections.css';

// Reveal invalid fields before native form validation attempts to focus them.
export function revealInvalidSetting(event:SyntheticEvent){
 let parent=(event.target as HTMLElement).parentElement;
 while(parent){if(parent instanceof HTMLDetailsElement)parent.open=true;parent=parent.parentElement;}
}
export default function SettingsSection({title,description,icon:Icon,children,initiallyOpen=false}: {
 title:string;description:string;icon:LucideIcon;children:ReactNode;initiallyOpen?:boolean;
}){
 return <details className="settingsSection" open={initiallyOpen||undefined} onInvalidCapture={revealInvalidSetting}>
  <summary><span className="settingsSectionIcon"><Icon size={21} aria-hidden="true"/></span><span className="settingsSectionLabel"><strong>{title}</strong><small>{description}</small></span><ChevronDown className="settingsSectionChevron" size={19} aria-hidden="true"/></summary>
  <div className="settingsSectionBody">{children}</div>
 </details>;
}
