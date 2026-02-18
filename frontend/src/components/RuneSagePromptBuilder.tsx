import React, { useState } from 'react';

interface RsBrief {
    context?: string;
    constraints?: string;
    technical_notes?: string[];
    suggested_prompt?: string;
}

interface RuneSagePromptBuilderProps {
    rsBrief: RsBrief;
    questTitle: string;
}

export const RuneSagePromptBuilder: React.FC<RuneSagePromptBuilderProps> = ({ rsBrief, questTitle }) => {
    const [copied, setCopied] = useState(false);

    const buildPrompt = (): string => {
        const parts: string[] = [];
        parts.push(`[Role: Expert Coder / RuneSage Assistant]`);
        parts.push(`[Quest: ${questTitle}]`);
        if (rsBrief.context) {
            parts.push(`[Context: ${rsBrief.context}]`);
        }
        if (rsBrief.constraints) {
            parts.push(`[Constraints: ${rsBrief.constraints}]`);
        }
        if (rsBrief.technical_notes && rsBrief.technical_notes.length > 0) {
            parts.push(`[Technical Notes:`);
            rsBrief.technical_notes.forEach((note) => {
                parts.push(`  - ${note}`);
            });
            parts.push(`]`);
        }
        parts.push('');
        if (rsBrief.suggested_prompt) {
            parts.push(rsBrief.suggested_prompt);
        } else {
            parts.push(`Please help me complete the quest "${questTitle}". Provide complete, working code.`);
        }
        parts.push('');
        parts.push('Return ONLY the complete code. Do not include explanations unless asked.');
        return parts.join('\n');
    };

    const handleCopy = () => {
        const prompt = buildPrompt();
        navigator.clipboard.writeText(prompt).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        });
    };

    return (
        <section className="border border-indigo-200 bg-gradient-to-br from-indigo-50 to-purple-50 rounded-lg p-4">
            <div className="flex items-center justify-between mb-3">
                <h3 className="text-sm font-bold text-indigo-800 flex items-center gap-2">
                    <span className="text-lg">🔮</span>
                    RuneSage Prompt Builder
                </h3>
                <span className="text-[10px] px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-600 font-medium">
                    AI Helper
                </span>
            </div>

            {rsBrief.context && (
                <div className="mb-2">
                    <span className="text-xs font-semibold text-indigo-700">Context:</span>
                    <p className="text-xs text-indigo-900 mt-0.5">{rsBrief.context}</p>
                </div>
            )}
            {rsBrief.constraints && (
                <div className="mb-2">
                    <span className="text-xs font-semibold text-indigo-700">Constraints:</span>
                    <p className="text-xs text-indigo-900 mt-0.5">{rsBrief.constraints}</p>
                </div>
            )}
            {(rsBrief.technical_notes?.length ?? 0) > 0 && (
                <div className="mb-2">
                    <span className="text-xs font-semibold text-indigo-700">Technical Notes:</span>
                    <ul className="list-disc list-inside text-xs text-indigo-900 mt-0.5 space-y-0.5">
                        {rsBrief.technical_notes?.map((note, i) => (
                            <li key={i}>{note}</li>
                        ))}
                    </ul>
                </div>
            )}
            {rsBrief.suggested_prompt && (
                <div className="mb-3">
                    <span className="text-xs font-semibold text-indigo-700">Suggested Prompt:</span>
                    <p className="text-xs text-indigo-900 mt-0.5 font-mono bg-white/60 border border-indigo-100 rounded p-2">
                        {rsBrief.suggested_prompt}
                    </p>
                </div>
            )}

            <div className="flex items-center gap-2 mt-3 pt-3 border-t border-indigo-100">
                <button
                    type="button"
                    onClick={handleCopy}
                    className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-all ${copied
                            ? 'bg-emerald-100 text-emerald-700 border border-emerald-200'
                            : 'bg-indigo-600 text-white hover:bg-indigo-700 border border-indigo-600'
                        }`}
                >
                    {copied ? (
                        <>
                            <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                            </svg>
                            Copied!
                        </>
                    ) : (
                        <>
                            <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                            </svg>
                            Copy Full Prompt
                        </>
                    )}
                </button>
                <a
                    href="https://chat.openai.com/"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 transition-colors"
                >
                    <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    Open ChatGPT
                </a>
            </div>
        </section>
    );
};
