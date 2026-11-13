import React from 'react';

function InlineContent({ text }) {
    const parts = String(text).split(/(\$[^$]+\$|\*\*[^*]+\*\*)/g).filter(Boolean);

    return parts.map((part, index) => {
        if (part.startsWith('$') && part.endsWith('$')) {
            return <span key={index} className="mx-0.5 rounded bg-slate-100 px-1.5 py-0.5 font-serif italic text-slate-900">{part.slice(1, -1)}</span>;
        }
        if (part.startsWith('**') && part.endsWith('**')) {
            return <strong key={index}>{part.slice(2, -2)}</strong>;
        }

        return <React.Fragment key={index}>{part}</React.Fragment>;
    });
}

function isTableSeparator(line) {
    return /^\s*\|?\s*:?-{3,}:?\s*(\|\s*:?-{3,}:?\s*)+\|?\s*$/.test(line);
}

function cells(line) {
    return line.trim().replace(/^\|/, '').replace(/\|$/, '').split('|').map((cell) => cell.trim());
}

export default function RichQuestionContent({ content = '', className = '' }) {
    const lines = String(content).replace(/\r\n/g, '\n').split('\n');
    const blocks = [];

    for (let index = 0; index < lines.length;) {
        if (index + 1 < lines.length && lines[index].includes('|') && isTableSeparator(lines[index + 1])) {
            const header = cells(lines[index]);
            const rows = [];
            index += 2;
            while (index < lines.length && lines[index].includes('|') && lines[index].trim()) {
                rows.push(cells(lines[index]));
                index += 1;
            }
            blocks.push(<div key={`table-${index}`} className="my-4 overflow-x-auto"><table className="w-full min-w-[420px] border-collapse text-sm"><thead><tr>{header.map((cell, cellIndex) => <th key={cellIndex} className="border bg-slate-50 px-3 py-2 text-left"><InlineContent text={cell}/></th>)}</tr></thead><tbody>{rows.map((row, rowIndex) => <tr key={rowIndex}>{row.map((cell, cellIndex) => <td key={cellIndex} className="border px-3 py-2"><InlineContent text={cell}/></td>)}</tr>)}</tbody></table></div>);
            continue;
        }
        if (lines[index].trim().startsWith('$$') && lines[index].trim().endsWith('$$') && lines[index].trim().length > 4) {
            blocks.push(<div key={`formula-${index}`} className="my-3 overflow-x-auto rounded-xl bg-slate-50 px-4 py-3 text-center font-serif text-lg italic text-slate-900">{lines[index].trim().slice(2, -2)}</div>);
        } else if (/^\s*[-*]\s+/.test(lines[index])) {
            const items = [];
            while (index < lines.length && /^\s*[-*]\s+/.test(lines[index])) {
                items.push(lines[index].replace(/^\s*[-*]\s+/, ''));
                index += 1;
            }
            blocks.push(<ul key={`list-${index}`} className="my-2 list-disc space-y-1 pl-6">{items.map((item, itemIndex) => <li key={itemIndex}><InlineContent text={item}/></li>)}</ul>);
            continue;
        } else if (lines[index].trim()) {
            blocks.push(<p key={`line-${index}`} className="min-h-4"><InlineContent text={lines[index]}/></p>);
        } else {
            blocks.push(<div key={`space-${index}`} className="h-3"/>);
        }
        index += 1;
    }

    return <div className={className}>{blocks}</div>;
}
