import axios from "axios";
import { query } from "@/api/utils";

export const http = axios.create({
    paramsSerializer: query,
});
