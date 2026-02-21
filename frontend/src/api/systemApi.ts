const API_BASE_URL =
  import.meta.env.VITE_API_BASE_URL ?? 'http://localhost:8000/api';

export interface AdventurerClass {
  id: string;
  label: string;
  icon: string;
}

export const fetchSystemClasses = async (): Promise<AdventurerClass[]> => {
  const response = await fetch(`${API_BASE_URL}/system/classes`);
  if (!response.ok) {
    throw new Error('Failed to fetch system classes');
  }
  const json = await response.json();
  return json.data ?? [];
};
