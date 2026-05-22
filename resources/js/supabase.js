import { createClient } from '@supabase/supabase-js';
import axios from 'axios';

const supabaseUrl = 'https://cuibxmcjdkgjffmmzwgd.supabase.co';
const supabaseKey = 'sb_publishable_5BPLZkbZvQXw7ZfrMWufFA_K77_nZxh';

export const supabase = createClient(supabaseUrl, supabaseKey);

export const api = axios.create({
  baseURL: `${supabaseUrl}/rest/v1`,
  headers: {
    apikey: supabaseKey,
    Authorization: `Bearer ${supabaseKey}`,
    'Content-Type': 'application/json',
  },
});