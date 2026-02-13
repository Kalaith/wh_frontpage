export interface QuestLabel {
    name: string;
    color: string;
}

export interface Quest {
    id: number;
    number: number;
    title: string;
    url: string;
    body: string;
    difficulty: number;
    class: string;
    xp: number;
    labels: QuestLabel[];
}
