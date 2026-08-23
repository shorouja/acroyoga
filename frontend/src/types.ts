export type Difficulty = 'beginner' | 'intermediate' | 'advanced'
export type Role = 'base' | 'flyer' | 'both' | 'solo'
export type SkillCategory = 'balance' | 'strength' | 'flexibility' | 'flow' | 'inversion'

export interface Exercise {
  '@id': string
  id: number
  name: string
  abbreviation: string | null
  difficulty: Difficulty
  role: Role
  description: string | null
  skills: string[] // IRIs, e.g. "/api/skills/3"
}

export interface Skill {
  '@id': string
  id: number
  name: string
  abbreviation: string | null
  category: SkillCategory
  description: string | null
  exercises: string[] // IRIs
}

export interface HydraCollection<T> {
  'hydra:member': T[]
  'hydra:totalItems': number
}
